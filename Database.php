<?php

/**
 * Database class for handling SQLite operations for the blog system
 */
class Database
{
    private $db;

    private $last_error;

    private $allowed_admin_roles = ['viewer', 'editor', 'admin'];

    /**
     * Constructor - initializes database connection and creates tables
     */
    public function __construct()
    {
        try {
            // Ensure data directory exists
            if (! is_dir(__DIR__ . '/data')) {
                mkdir(__DIR__ . '/data', 0777, true);
            }
            $this->db = new SQLite3(__DIR__ . '/data/blog.db');
            $this->createTables();
            $this->ensureAdminRoleColumn();
            $this->createAuditLogTable();
            $this->createMediaTable();
            $this->initializeSettings();
            $this->initializeDefaultAdmin();
        } catch (Exception $e) {
            $this->last_error = 'Database connection error: ' . $e->getMessage();
            error_log($this->last_error);
            exit($this->last_error);
        }
    }

    /**
     * Creates required database tables if they don't exist
     */
    private function createTables()
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT "editor",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        $this->db->exec('CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            category TEXT NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            article_image TEXT,
            slug TEXT UNIQUE,
            meta_title TEXT,
            meta_description TEXT
        )');

        $this->db->exec('CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_name TEXT UNIQUE NOT NULL,
            setting_value TEXT
        )');
    }

    private function ensureAdminRoleColumn(): void
    {
        $columnExists = false;
        $result = $this->db->query('PRAGMA table_info(admins)');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (isset($row['name']) && 'role' === $row['name']) {
                $columnExists = true;
                break;
            }
        }

        if (! $columnExists) {
            $this->db->exec('ALTER TABLE admins ADD COLUMN role TEXT NOT NULL DEFAULT "editor"');
            $this->db->exec('UPDATE admins SET role = "editor" WHERE role IS NULL OR role = ""');
        }
    }

    private function createAuditLogTable(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER,
            action TEXT NOT NULL,
            entity_type TEXT,
            entity_id INTEGER,
            metadata TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(admin_id) REFERENCES admins(id)
        )');
    }

    private function createMediaTable(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS media_files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            original_filename TEXT NOT NULL,
            storage_path TEXT NOT NULL,
            mime_type TEXT NOT NULL,
            size_bytes INTEGER NOT NULL,
            width INTEGER,
            height INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
    }

    /**
     * Initializes default blog settings if they don't exist
     */
    private function initializeSettings()
    {
        $result = $this->db->querySingle("SELECT COUNT(*) FROM settings WHERE setting_name = 'blog_title'");
        if (0 == $result) {
            $this->db->exec("INSERT INTO settings (setting_name, setting_value) VALUES ('blog_title', '~/chernega.blog')");
        }

        $result = $this->db->querySingle("SELECT COUNT(*) FROM settings WHERE setting_name = 'posts_per_page'");
        if (0 == $result) {
            $this->db->exec("INSERT INTO settings (setting_name, setting_value) VALUES ('posts_per_page', '5')");
        }
    }

    private function initializeDefaultAdmin(): void
    {
        $count = (int) $this->db->querySingle('SELECT COUNT(*) FROM admins');
        if ($count > 0) {
            return;
        }

        $defaultAdmin = $this->getConfigValue('default_admin', []);
        $username = $defaultAdmin['username'] ?? 'admin';
        $passwordHash = $defaultAdmin['password_hash'] ?? null;
        $fallbackPassword = $defaultAdmin['password'] ?? 'admin123';
        $role = $defaultAdmin['role'] ?? 'admin';

        if (empty($passwordHash) && ! empty($fallbackPassword)) {
            $passwordHash = password_hash($fallbackPassword, PASSWORD_DEFAULT);
        }

        if (empty($passwordHash)) {
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        }

        $stmt = $this->db->prepare('INSERT INTO admins (username, password, role) VALUES (:username, :password, :role)');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', $passwordHash, SQLITE3_TEXT);
        $stmt->bindValue(':role', $role, SQLITE3_TEXT);
        $stmt->execute();
    }

    /**
     * Gets all blog settings as an associative array
     *
     * @return array Associative array of setting_name => setting_value
     */
    public function getBlogSettings()
    {
        $settings = [];
        $result = $this->db->query('SELECT setting_name, setting_value FROM settings');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }

        return $settings;
    }

    /**
     * Fetches an admin record by username
     */
    public function getAdminByUsername(string $username)
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();

        $row = $result->fetchArray(SQLITE3_ASSOC);

        return $row ?: null;
    }

    public function getAdminById(int $id)
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();

        $row = $result->fetchArray(SQLITE3_ASSOC);

        return $row ?: null;
    }

    public function getAdmins(): array
    {
        $rows = [];
        $result = $this->db->query('SELECT id, username, role, created_at FROM admins ORDER BY username ASC');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function updateAdminRole(int $adminId, string $role): bool
    {
        $role = strtolower($role);
        if (! in_array($role, $this->allowed_admin_roles, true)) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE admins SET role = :role WHERE id = :id');
        $stmt->bindValue(':role', $role, SQLITE3_TEXT);
        $stmt->bindValue(':id', $adminId, SQLITE3_INTEGER);

        return (bool) $stmt->execute();
    }

    public function getAllowedAdminRoles(): array
    {
        return $this->allowed_admin_roles;
    }

    public function logAudit(?int $adminId, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = [], ?string $ipAddress = null): void
    {
        $stmt = $this->db->prepare('INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, metadata, ip_address) VALUES (:admin_id, :action, :entity_type, :entity_id, :metadata, :ip_address)');
        if (null === $adminId) {
            $stmt->bindValue(':admin_id', null, SQLITE3_NULL);
        } else {
            $stmt->bindValue(':admin_id', $adminId, SQLITE3_INTEGER);
        }
        $stmt->bindValue(':action', $action, SQLITE3_TEXT);
        $stmt->bindValue(':entity_type', $entityType, SQLITE3_TEXT);
        if (null === $entityId) {
            $stmt->bindValue(':entity_id', null, SQLITE3_NULL);
        } else {
            $stmt->bindValue(':entity_id', $entityId, SQLITE3_INTEGER);
        }
        $stmt->bindValue(':metadata', json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), SQLITE3_TEXT);
        $stmt->bindValue(':ip_address', $ipAddress, SQLITE3_TEXT);
        $stmt->execute();
    }

    public function getRecentAuditLogs(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        $stmt = $this->db->prepare('SELECT audit_logs.*, admins.username FROM audit_logs LEFT JOIN admins ON audit_logs.admin_id = admins.id ORDER BY audit_logs.created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();

        return $this->fetchAll($result);
    }
    /**
     * Saves blog settings to database
     *
     * @param  string  $blog_title  The blog title
     * @param  int  $posts_per_page  Number of posts per page
     * @return int|false Inserted post ID on success, false on failure
     */
    public function saveBlogSettings($blog_title, $posts_per_page)
    {
        $this->db->exec('BEGIN TRANSACTION;');
        try {
            $stmt = $this->db->prepare("INSERT INTO settings (setting_name, setting_value) VALUES ('blog_title', :blog_title) ON CONFLICT(setting_name) DO UPDATE SET setting_value = :blog_title_update");
            $stmt->bindValue(':blog_title', $blog_title, SQLITE3_TEXT);
            $stmt->bindValue(':blog_title_update', $blog_title, SQLITE3_TEXT);
            $stmt->execute();

            $stmt = $this->db->prepare("INSERT INTO settings (setting_name, setting_value) VALUES ('posts_per_page', :posts_per_page) ON CONFLICT(setting_name) DO UPDATE SET setting_value = :posts_per_page_update");
            $stmt->bindValue(':posts_per_page', $posts_per_page, SQLITE3_INTEGER);
            $stmt->bindValue(':posts_per_page_update', $posts_per_page, SQLITE3_INTEGER);
            $stmt->execute();

            $this->db->exec('COMMIT;');

            return true;
        } catch (Exception $e) {
            $this->db->exec('ROLLBACK;');
            $this->last_error = 'Settings save error: ' . $e->getMessage();
            error_log($this->last_error);

            return false;
        }
    }

    /**
     * Gets the last error message
     *
     * @return string Last error message
     */
    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * Counts all posts in database
     *
     * @return int Total post count
     */
    public function countAllPosts()
    {
        $result = $this->db->query('SELECT COUNT(*) FROM posts');

        return $result->fetchArray(SQLITE3_ASSOC)['COUNT(*)'];
    }

    /**
     * Counts posts matching search term
     *
     * @param  string  $search  Search term
     * @return int Number of matching posts
     */
    public function countSearchPosts($search)
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM posts WHERE title LIKE :search OR content LIKE :search');
        $search_term = "%{$search}%";
        $stmt->bindValue(':search', $search_term, SQLITE3_TEXT);
        $result = $stmt->execute();

        return $result->fetchArray(SQLITE3_ASSOC)['COUNT(*)'];
    }

    /**
     * Gets all posts with optional pagination
     *
     * @param  int|null  $limit  Maximum number of posts to return
     * @param  int|null  $offset  Offset for pagination
     * @return array Array of post data
     */
    public function getAllPosts($limit = null, $offset = null)
    {
        $sql = 'SELECT * FROM posts ORDER BY created_at DESC';
        if (null !== $limit) {
            $sql .= ' LIMIT :limit';
            if (null !== $offset) {
                $sql .= ' OFFSET :offset';
            }
        }

        $stmt = $this->db->prepare($sql);
        if (null !== $limit) {
            $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
            if (null !== $offset) {
                $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            }
        }
        $result = $stmt->execute();

        return $this->fetchAll($result);
    }

    /**
     * Searches posts with optional pagination
     *
     * @param  string  $search  Search term
     * @param  int|null  $limit  Maximum number of posts to return
     * @param  int|null  $offset  Offset for pagination
     * @return array Array of matching posts
     */
    public function searchPosts($search, $limit = null, $offset = null)
    {
        $sql = 'SELECT * FROM posts WHERE title LIKE :search OR content LIKE :search ORDER BY created_at DESC';
        if (null !== $limit) {
            $sql .= ' LIMIT :limit';
            if (null !== $offset) {
                $sql .= ' OFFSET :offset';
            }
        }

        $stmt = $this->db->prepare($sql);
        $search_term = "%{$search}%";
        $stmt->bindValue(':search', $search_term, SQLITE3_TEXT);
        if (null !== $limit) {
            $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
            if (null !== $offset) {
                $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            }
        }
        $result = $stmt->execute();

        return $this->fetchAll($result);
    }

    /**
     * Gets most recent posts
     *
     * @param  int  $limit  Number of posts to return
     * @return array Array of recent posts
     */
    public function getRecentPosts($limit = 5)
    {
        $stmt = $this->db->prepare('
            SELECT * FROM posts
            ORDER BY created_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();

        return $this->fetchAll($result);
    }

    /**
     * Gets a single post by ID
     *
     * @param  int  $id  Post ID
     * @return array|false Post data or false if not found
     */
    public function getPost($id)
    {
        $stmt = $this->db->prepare('
            SELECT * FROM posts
            WHERE id = :id
        ');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();

        return $result->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * Gets a single post by slug
     *
     * @param  string  $slug  Post slug
     * @return array|false Post data or false if not found
     */
    public function getPostBySlug($slug)
    {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM posts
                WHERE slug = :slug
            ');
            $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
            $result = $stmt->execute();

            return $result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            $this->last_error = 'Error getting post by slug: ' . $e->getMessage();
            error_log($this->last_error);

            return false;
        }
    }

    /**
     * Adds a new post
     *
     * @param  string  $title  Post title
     * @param  string  $category  Post category
     * @param  string  $content  Post content
     * @param  string|null  $article_image  Featured image path
     * @param  string|null  $slug  Custom slug (optional)
     * @param  string|null  $meta_title  Custom meta title (optional)
     * @param  string|null  $meta_description  Custom meta description (optional)
     * @return int|false Inserted post ID on success, false on failure
     */
    public function addPost($title, $category, $content, $article_image = null, $slug = null, $meta_title = null, $meta_description = null)
    {
        if (empty($slug)) {
            $slug = $this->generateUniqueSlug($title);
        } else {
            $slug = $this->generateUniqueSlug($slug);
        }

        $stmt = $this->db->prepare('
            INSERT INTO posts (title, category, content, article_image, slug, meta_title, meta_description)
            VALUES (:title, :category, :content, :article_image, :slug, :meta_title, :meta_description)
        ');
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        $stmt->bindValue(':content', $content, SQLITE3_TEXT);
        $stmt->bindValue(':article_image', $article_image, SQLITE3_TEXT);
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $stmt->bindValue(':meta_title', $meta_title, SQLITE3_TEXT);
        $stmt->bindValue(':meta_description', $meta_description, SQLITE3_TEXT);

        $result = $stmt->execute();
        if (false === $result) {
            $this->last_error = $this->db->lastErrorMsg();

            return false;
        }

        return (int) $this->db->lastInsertRowID();
    }

    /**
     * Updates an existing post
     *
     * @param  int  $id  Post ID
     * @param  string  $title  Post title
     * @param  string  $category  Post category
     * @param  string  $content  Post content
     * @param  string  $created_at  Post creation datetime (format: Y-m-d H:i:s)
     * @param  string|null  $article_image  Featured image path
     * @param  string|null  $slug  Custom slug (optional)
     * @param  string|null  $meta_title  Custom meta title (optional)
     * @param  string|null  $meta_description  Custom meta description (optional)
     * @return int|false Inserted post ID on success, false on failure
     */
    public function updatePost($id, $title, $category, $content, $created_at, $article_image = null, $slug = null, $meta_title = null, $meta_description = null)
    {
        $current_post = $this->getPost($id);
        $old_slug = $current_post['slug'] ?? null;

        if (empty($slug)) {
            $slug = $this->generateUniqueSlug($title, $id);
        } elseif ($slug !== $old_slug) {
            $slug = $this->generateUniqueSlug($slug, $id);
        } else {
            $slug = $old_slug;
        }

        $stmt = $this->db->prepare('
            UPDATE posts SET
                title = :title,
                category = :category,
                content = :content,
                created_at = :created_at,
                updated_at = CURRENT_TIMESTAMP,
                article_image = :article_image,
                slug = :slug,
                meta_title = :meta_title,
                meta_description = :meta_description
            WHERE id = :id
        ');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        $stmt->bindValue(':content', $content, SQLITE3_TEXT);
        $stmt->bindValue(':created_at', $created_at, SQLITE3_TEXT);
        $stmt->bindValue(':article_image', $article_image, SQLITE3_TEXT);
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $stmt->bindValue(':meta_title', $meta_title, SQLITE3_TEXT);
        $stmt->bindValue(':meta_description', $meta_description, SQLITE3_TEXT);

        if (! $stmt->execute()) {
            $this->last_error = $this->db->lastErrorMsg();
            return false;
        }

        return true;
    }

    /**
     * Deletes a post
     *
     * @param  int  $id  Post ID to delete
     * @return int|false Inserted post ID on success, false on failure
     */
    public function deletePost($id)
    {
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        if (false === $result) {
            $this->last_error = $this->db->lastErrorMsg();

            return false;
        }

        return (int) $this->db->lastInsertRowID();
    }

    /**
     * Gets all unique categories from the database
     *
     * @return array Array of category names
     */
    public function getAllCategories()
    {
        $categories = [];
        $result = $this->db->query('SELECT DISTINCT category FROM posts ORDER BY category ASC');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $categories[] = $row['category'];
        }
        return $categories;
    }

    /**
     * Counts posts belonging to a specific category
     *
     * @param  string  $category  Category name
     * @return int Number of posts in the category
     */
    public function countPostsByCategory($category)
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM posts WHERE category = :category');
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC)['COUNT(*)'];
    }

    /**
     * Gets posts by category with optional pagination
     *
     * @param  string  $category  Category name
     * @param  int|null  $limit  Maximum number of posts to return
     * @param  int|null  $offset  Offset for pagination
     * @return array Array of post data
     */
    public function getPostsByCategory($category, $limit = null, $offset = null)
    {
        $sql = 'SELECT * FROM posts WHERE category = :category ORDER BY created_at DESC';
        if (null !== $limit) {
            $sql .= ' LIMIT :limit';
            if (null !== $offset) {
                $sql .= ' OFFSET :offset';
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        if (null !== $limit) {
            $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
            if (null !== $offset) {
                $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            }
        }
        $result = $stmt->execute();

        return $this->fetchAll($result);
    }

    /**
     * Fetches all rows from a query result
     *
     * @param  SQLite3Result  $result  Query result
     * @return array Array of rows
     */
    private function fetchAll($result)
    {
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function getConfigValue(string $key, $default = null)
    {
        static $config = null;
        if (null === $config) {
            $configFile = __DIR__ . '/config/app.php';
            if (file_exists($configFile)) {
                $data = require $configFile;
                if (is_array($data)) {
                    $config = $data;
                }
            }

            if (null === $config) {
                $config = [];
            }
        }

        return $config[$key] ?? $default;
    }

    /**
     * Generates a unique slug for a post
     *
     * @param  string  $text  Text to convert to slug
     * @param  int|null  $exclude_id  Post ID to exclude from uniqueness check
     * @return string Unique slug
     */
    private function generateUniqueSlug($text, $exclude_id = null)
    {
        $slug = $this->slugify($text);
        $original_slug = $slug;
        $counter = 1;
        while ($this->slugExists($slug, $exclude_id)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Converts text to a URL-friendly slug
     *
     * @param  string  $text  Text to convert
     * @return string Generated slug
     */
    private function slugify($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Checks if a slug already exists
     *
     * @param  string  $slug  Slug to check
     * @param  int|null  $exclude_id  Post ID to exclude from check
     * @return bool True if slug exists, false otherwise
     */
    private function slugExists($slug, $exclude_id = null)
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE slug = :slug';
        if (null !== $exclude_id) {
            $sql .= ' AND id != :exclude_id';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        if (null !== $exclude_id) {
            $stmt->bindValue(':exclude_id', $exclude_id, SQLITE3_INTEGER);
        }
        $result = $stmt->execute();

        return $result->fetchArray(SQLITE3_ASSOC)['COUNT(*)'] > 0;
    }

    /**
     * Authenticates an admin user
     *
     * @param  string  $username  Admin username
     * @param  string  $password  Admin password
     * @return array|false Admin data if authenticated, false otherwise
     */
    public function authenticateAdmin($username, $password)
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $admin = $result->fetchArray(SQLITE3_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            return $admin;
        }

        return false;
    }

    /**
     * Adds a new admin user
     *
     * @param  string  $username  Admin username
     * @param  string  $password  Admin password
     * @return int|false Inserted post ID on success, false on failure
     */
    public function addAdmin($username, $password)
    {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('INSERT INTO admins (username, password) VALUES (:username, :password)');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
        $result = $stmt->execute();
        if (false === $result) {
            $this->last_error = $this->db->lastErrorMsg();

            return false;
        }

        return (int) $this->db->lastInsertRowID();
    }

    /**
     * Checks if any admin users exist
     *
     * @return bool True if admins exist, false otherwise
     */
    public function hasAdmins()
    {
        $result = $this->db->querySingle('SELECT COUNT(*) FROM admins');

        return $result > 0;
    }
}
?>
