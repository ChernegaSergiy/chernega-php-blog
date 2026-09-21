<?php

function media_process_upload(array $file): array
{
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }

    // Create the destination directory if it doesn't exist
    $year = date('Y');
    $month = date('m');
    $directory_path = "uploads/{$year}/{$month}/";
    $absolute_directory_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $directory_path;

    if (!is_dir($absolute_directory_path)) {
        if (!mkdir($absolute_directory_path, 0755, true)) {
            throw new Exception('Failed to create upload directory.');
        }
    }

    // Generate a unique filename
    $original_filename = basename($file['name']);
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    $new_filename = bin2hex(random_bytes(8)) . '.' . $extension;
    $absolute_path = $absolute_directory_path . $new_filename;

    // Move the file
    if (!move_uploaded_file($file['tmp_name'], $absolute_path)) {
        throw new Exception('Failed to move uploaded file.');
    }

    // Get image dimensions if it's an image
    $width = null;
    $height = null;
    if (str_starts_with($file['type'], 'image/')) {
        [$width, $height] = getimagesize($absolute_path);
    }

    return [
        'filename' => $new_filename,
        'original_filename' => $original_filename,
        'relative_path' => $directory_path . $new_filename,
        'absolute_path' => $absolute_path,
        'mime_type' => $file['type'],
        'size_bytes' => $file['size'],
        'width' => $width,
        'height' => $height,
    ];
}

function media_relative_to_absolute(string $relative_path): string
{
    return $_SERVER['DOCUMENT_ROOT'] . '/' . $relative_path;
}

function media_cleanup_storage(Database $db): array
{
    $removed_files = 0;
    $removed_records = 0;
    $removed_directories = 0;

    $db_media_files = $db->getMediaFiles(null, 0); // Get all media files from DB
    $db_paths = array_column($db_media_files, 'storage_path', 'id');

    $uploaded_files_on_disk = [];
    $uploads_root = $_SERVER['DOCUMENT_ROOT'] . '/uploads';

    // Recursively get all files in the uploads directory
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads_root));
    foreach ($rii as $file) {
        if ($file->isDir()) {
            continue;
        }
        $path = str_replace($uploads_root . '/', '', $file->getPathname());
        $uploaded_files_on_disk[] = $path;
    }

    // 1. Remove files on disk that are not in the database
    foreach ($uploaded_files_on_disk as $disk_path) {
        if (!in_array($disk_path, $db_paths)) {
            $absolute_path = media_relative_to_absolute($disk_path);
            if (file_exists($absolute_path) && @unlink($absolute_path)) {
                $removed_files++;
            }
        }
    }

    // 2. Remove database records for files that no longer exist on disk
    foreach ($db_media_files as $media_item) {
        $absolute_path = media_relative_to_absolute($media_item['storage_path']);
        if (!file_exists($absolute_path)) {
            if ($db->deleteMedia($media_item['id'])) {
                $removed_records++;
            }
        }
    }

    // 3. Remove empty directories
    $dirs = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads_root), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($rii as $file) {
        if ($file->isDir()) {
            $dirs[] = $file->getPathname();
        }
    }

    // Sort directories by length in descending order to delete deepest first
    usort($dirs, function ($a, $b) {
        return strlen($b) - strlen($a);
    });

    foreach ($dirs as $dir) {
        // Check if directory is empty (excluding . and ..)
        if (is_dir($dir) && count(scandir($dir)) == 2) {
            if (@rmdir($dir)) {
                $removed_directories++;
            }
        }
    }

    return [
        'removed_files' => $removed_files,
        'removed_records' => $removed_records,
        'removed_directories' => $removed_directories,
    ];
}


