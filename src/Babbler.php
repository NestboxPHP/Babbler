<?php

declare(strict_types=1);

namespace NestboxPHP\Babbler;

use NestboxPHP\Nestbox\Nestbox;
use NestboxPHP\Babbler\Exception\BabblerException;

class Babbler extends Nestbox
{
    final protected const PACKAGE_NAME = 'babbler';

    public int $babblerAuthorSize = 32;
    public int $babblerCategorySize = 64;
    public int $babblerSubCategorySize = 64;
    public int $babblerTitleSize = 255;

    /**
     * Entry Management
     *  _____       _                __  __                                                   _
     * | ____|_ __ | |_ _ __ _   _  |  \/  | __ _ _ __   __ _  __ _  ___ _ __ ___   ___ _ __ | |_
     * |  _| | '_ \| __| '__| | | | | |\/| |/ _` | '_ \ / _` |/ _` |/ _ \ '_ ` _ \ / _ \ '_ \| __|
     * | |___| | | | |_| |  | |_| | | |  | | (_| | | | | (_| | (_| |  __/ | | | | |  __/ | | | |_
     * |_____|_| |_|\__|_|   \__, | |_|  |_|\__,_|_| |_|\__,_|\__, |\___|_| |_| |_|\___|_| |_|\__|
     *                       |___/                            |___/
     */


    /**
     * Adds a new babbler entry
     *
     * @param string $category
     * @param string $subCategory
     * @param string $title
     * @param string $content
     * @param string $author
     * @param string|null $created
     * @param string|null $published
     * @param bool $isDraft
     * @param bool $isHidden
     * @return int|false
     * @throws BabblerException
     */
    public function add_entry(string $category, string $subCategory, string $title, string $content, string $author,
                              string $created = null, string $published = null, bool $isDraft = false,
                              bool   $isHidden = false): int|false
    {
        // prepare vars and verify entry
        $emptyStrings = [];
        if (0 == strlen(trim($category))) $emptyStrings[] = "'category'";
        if (0 == strlen(trim($subCategory))) $emptyStrings[] = "'sub_category'";
        if (0 == strlen(trim($title))) $emptyStrings[] = "'title'";
        if (0 == strlen(trim($content))) $emptyStrings[] = "'content'";
        if (0 == strlen(trim($author))) $emptyStrings[] = "'author'";

        if ($emptyStrings) {
            throw new BabblerException("Empty strings provided for: " . join(", ", $emptyStrings));
        }

        $params = [
            "category" => trim($category),
            "sub_category" => trim($subCategory),
            "title" => trim($title),
            "content" => trim($content),
            "created_by" => trim($author),
            "edited_by" => trim($author),
            "created" => date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: ($created ?? "now"))),
            "edited" => date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: ($created ?? "now"))),
            "published" => ($published) ? date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: $published)) : null,
            "is_draft" => $isDraft,
            "is_hidden" => $isHidden,
        ];

        // create query
        $sql = "INSERT INTO `babbler_entries` (
                    `category`
                    , `sub_category`
                    , `title`
                    , `content`
                    , `created_by`
                    , `edited_by`
                    , `created`
                    , `edited`
                    , `published`
                    , `is_draft`
                    , `is_hidden`
                ) VALUES (
                    :category
                    , :sub_category
                    , :title
                    , :content
                    , :created_by
                    , :edited_by
                    , :created
                    , :edited
                    , :published
                    , :is_draft
                    , :is_hidden
                );";

        // execute
        if (!$this->query_execute($sql, $params)) return false;
        return $this->get_row_count();
    }


    /**
     * Edits an existing entry
     *
     * @param string|int $entryId
     * @param string $editor
     * @param string $category
     * @param string $subCateogyr
     * @param string $title
     * @param string $content
     * @param string $published
     * @param bool|null $isDraft
     * @param bool|null $isHidden
     * @return bool
     */
    public function edit_entry(string|int $entryId, string $editor, string $category = "", string $subCateogyr = "",
                               string     $title = "", string $content = "", string $published = "",
                               bool       $isDraft = null, bool $isHidden = null): int|false
    {
        // verify entry data
        if (0 == intval($entryId)) {
            $this->err[] = "Missing data for entry: 'Entry ID'";
            return false;
        }

        $params = [
            "entry_id" => $entryId,
            "edited_by" => $editor
        ];

        if (!empty(trim($category))) $params['category'] = trim($category);
        if (!empty(trim($subCateogyr))) $params['sub_category'] = trim($subCateogyr);
        if (!empty(trim($title))) $params['title'] = trim($title);
        if (!empty(trim($content))) $params['content'] = trim($content);
        if (preg_match('/\d[4]\-\d\d\-\d\d.\d\d(\:\d\d(\:\d\d)?)?/', $published, $t)) {
            $params["published"] = date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: $published));
        }
        $params["is_draft"] = (isset($isDraft)) ? $isDraft : false;
        $params["is_hidden"] = (isset($isHidden)) ? $isHidden : false;

        $cols = [];
        foreach ($params as $column => $value) $cols[] = "`$column` = :$column";
        $cols = implode(", ", $cols);

        // create query
        $sql = "UPDATE `babbler_entries` SET $cols WHERE `entry_id` = :entry_id;";

        // execute
        if (!$this->query_execute($sql, $params)) return false;
        return $this->get_row_count();
    }


    /**
     * Deletes an existing entry
     *
     * @param int $entry_id
     * @return bool
     */
    public function delete_entry(int $entry_id): bool
    {
        return $this->delete("babbler_entries", ["entry_id" => $entry_id]);
    }


    /**
     * Entry Search
     *  _____       _                ____                      _
     * | ____|_ __ | |_ _ __ _   _  / ___|  ___  __ _ _ __ ___| |__
     * |  _| | '_ \| __| '__| | | | \___ \ / _ \/ _` | '__/ __| '_ \
     * | |___| | | | |_| |  | |_| |  ___) |  __/ (_| | | | (__| | | |
     * |_____|_| |_|\__|_|   \__, | |____/ \___|\__,_|_|  \___|_| |_|
     *                       |___/
     */


    /**
     * Search entry contents for exact string
     *
     * @param string $search
     * @param string $category
     * @return array
     */
    public function search_entries_exact(string $search, string $category = "*"): array
    {
        $where = ["content LIKE" => "%" . trim($search) . "%"];

        if ("*" != $category) $where["category"] = $category;

        return $this->select("babbler_entries", $where);
    }


    /**
     * Search entry contents for fuzzy string
     *
     * @param string $search
     * @param string $category
     * @return array
     */
    public function search_entries_fuzzy(string $search, string $category = "*"): array
    {
        $search = $this->sanitize_search_string($search);

        $search = implode("%", preg_split(pattern: "/\s+/", subject: $search));

        $where = ["content" => "%$search%"];
        if ("*" != $category) $where["category"] = trim($category);

        return $this->select("babbler_entries", $where);
    }


    /**
     * Search entry contents for words, returning the result set in match total descending order
     *
     * @param string $words
     * @param string $category
     * @return array
     */
    public function search_entries_threshold(string $words, string $category = "*"): array
    {
        $cases = [];

        foreach (explode(" ", $this->sanitize_search_string($words)) as $word) {
            $word = preg_replace("/[^\w]+/", "", $word);

            $cases[] = "CASE WHEN FIND IN SET('$word', `content`) > 0 THEN 1 ELSE 0 END";
        }

        $cases = implode(" + ", $cases);

        $sql = "SELECT *, SUM($cases) AS 'threshold' FROM `babbler_entries` ORDER BY `threashold` DESC;";

        if (!$this->query_execute($sql)) return [];

        return $this->fetch_all_results();
    }


    /**
     * Search entry contents for regex string
     *
     * @param string $pattern
     * @param string $category
     * @return array
     */
    public function search_entries_regex(string $pattern, string $category = "*"): array
    {
        $sql = "SELECT * FROM `babbler_entries` WHERE REGEXP :pattern";

        $params = ["pattern" => $pattern];

        if (!$this->query_execute($sql, $params)) return [];

        return $this->fetch_all_results();
    }


    /**
     * Search entry titles for exact string
     *
     * @param string $title
     * @return array
     */
    public function search_title(string $title): array
    {
        $title = $this->sanitize_search_string($title);

        return $this->select("babbler_entries", ["title LIKE" => "%$title%"]);
    }


    /**
     * Remove all non word and space characters from a string
     *
     * @param string $string
     * @return string
     */
    protected function sanitize_search_string(string $string): string
    {
        return preg_replace(pattern: "/[^\w\s]+/i", replacement: "", subject: $string);
    }


    /**
     * Class Tables
     *   ____ _                 _____     _     _
     *  / ___| | __ _ ___ ___  |_   _|_ _| |__ | | ___  ___
     * | |   | |/ _` / __/ __|   | |/ _` | '_ \| |/ _ \/ __|
     * | |___| | (_| \__ \__ \   | | (_| | |_) | |  __/\__ \
     *  \____|_|\__,_|___/___/   |_|\__,_|_.__/|_|\___||___/
     *
     */


    public function create_class_table_babbler_entries(): bool
    {
        // check if entry table exists
        if ($this->valid_schema('babbler_entries')) return true;

        $sql = "CREATE TABLE IF NOT EXISTS `babbler_entries` (
                    `entry_id` INT NOT NULL AUTO_INCREMENT ,
                    `created` DATETIME NOT NULL ,
                    `edited` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
                    `published` DATETIME NULL ,
                    `is_draft` TINYINT( 1 ) NOT NULL DEFAULT 0 ,
                    `is_hidden` TINYINT( 1 ) NOT NULL DEFAULT 0 ,
                    `created_by` VARCHAR( {$this->babblerAuthorSize} ) NOT NULL ,
                    `edited_by` VARCHAR( {$this->babblerAuthorSize} ) NOT NULL ,
                    `category` VARCHAR( {$this->babblerCategorySize} ) NOT NULL ,
                    `sub_category` VARCHAR( {$this->babblerSubCategorySize} ) NOT NULL ,
                    `title` VARCHAR( {$this->babblerTitleSize} ) NOT NULL ,
                    `content` MEDIUMTEXT NOT NULL ,
                    PRIMARY KEY ( `entry_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";
        return $this->query_execute($sql);
    }


    public function create_class_table_babbler_history(): bool
    {
        // check if history table exists
        if ($this->valid_schema('babbler_history')) return true;

        // create the history table
        $sql = "CREATE TABLE IF NOT EXISTS `babbler_history` (
                    `history_id` INT NOT NULL AUTO_INCREMENT ,
                    `entry_id` INT NOT NULL ,
                    `created` DATETIME NOT NULL ,
                    `edited` TIMESTAMP ,
                    `published` DATETIME NULL ,
                    `is_draft` TINYINT( 1 ) NOT NULL DEFAULT 0 ,
                    `is_hidden` TINYINT( 1 ) NOT NULL DEFAULT 0 ,
                    `created_by` VARCHAR( {$this->babblerAuthorSize} ) NOT NULL ,
                    `edited_by` VARCHAR( {$this->babblerAuthorSize} ) NOT NULL ,
                    `category` VARCHAR( {$this->babblerCategorySize} ) NOT NULL ,
                    `sub_category` VARCHAR( {$this->babblerSubCategorySize} ) NOT NULL ,
                    `title` VARCHAR( {$this->babblerTitleSize} ) NOT NULL ,
                    `content` MEDIUMTEXT NOT NULL ,
                    PRIMARY KEY ( `history_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";

        if (!$this->query_execute($sql)) return false;

        // create history trigger
        $sql = "CREATE TRIGGER IF NOT EXISTS `babbler_history_trigger` AFTER UPDATE ON `babbler_entries`
                FOR EACH ROW
                IF ( OLD.edited <> NEW.edited ) THEN
                    INSERT INTO `babbler_history` (
                        `entry_id`
                        , `created`
                        , `edited`
                        , `published`
                        , `is_draft`
                        , `is_hidden`
                        , `created_by`
                        , `edited_by`
                        , `category`
                        , `sub_category`
                        , `title`
                        , `content`
                    ) VALUES (
                        OLD.`entry_id`
                        , OLD.`created`
                        , OLD.`edited`
                        , OLD.`published`
                        , OLD.`is_draft`
                        , OLD.`is_hidden`
                        , OLD.`created_by`
                        , OLD.`edited_by`
                        , OLD.`category`
                        , OLD.`sub_category`
                        , OLD.`title`
                        , OLD.`content`
                    );
                END IF;
                CREATE TRIGGER `babbler_delete_trigger` BEFORE DELETE
                ON `babbler_entries` FOR EACH ROW
                BEGIN
                    INSERT INTO `babbler_history` (
                        `entry_id`
                        , `created`
                        , `edited`
                        , `published`
                        , `is_draft`
                        , `is_hidden`
                        , `created_by`
                        , `edited_by`
                        , `category`
                        , `sub_category`
                        , `title`
                        , `content`
                    ) VALUES (
                        OLD.`entry_id`
                        , OLD.`created`
                        , OLD.`edited`
                        , OLD.`published`
                        , OLD.`is_draft`
                        , OLD.`is_hidden`
                        , OLD.`created_by`
                        , OLD.`edited_by`
                        , OLD.`category`
                        , OLD.`sub_category`
                        , OLD.`title`
                        , OLD.`content`
                    );
                END;";

        // todo: check if trigger added and delete table if trigger creation fails
        return $this->query_execute($sql);
    }


    /**
     * Entry Fetch
     *  _____       _                _____    _       _
     * | ____|_ __ | |_ _ __ _   _  |  ___|__| |_ ___| |__
     * |  _| | '_ \| __| '__| | | | | |_ / _ \ __/ __| '_ \
     * | |___| | | | |_| |  | |_| | |  _|  __/ || (__| | | |
     * |_____|_| |_|\__|_|   \__, | |_|  \___|\__\___|_| |_|
     *                       |___/
     */


    public function fetch_entry_table(string $orderBy = "", string $sort = "", int $limit = 50, int $start = 0): array
    {
        $orderBy = (!empty($orderBy) && $this->valid_schema('babbler_entries', $orderBy)) ? $orderBy : 'created';
        $sort = (in_array(strtoupper($sort), array('ASC', 'DESC'))) ? strtoupper($sort) : 'ASC';
        $sql = "SELECT * FROM `babbler_entries` ORDER BY `{$orderBy}` {$sort};";

        return ($this->query_execute($sql)) ? $this->fetch_all_results() : [];
    }


    public function fetch_entry(int $entry_id): array
    {
        $sql = "SELECT * FROM `babbler_entries` WHERE `entry_id` = :entryID;";
        $this->query_execute($sql, array('entryID' => $entry_id));
        return $this->fetch_all_results()[0] ?? [];
    }


    public function fetch_categories(): array
    {
        $output = [];
        $sql = "SELECT `category`, COUNT(*) as `count` FROM `babbler_entries` GROUP BY `category`;";

        if (!$this->query_execute($sql)) return $output;

        foreach ($this->fetch_all_results() as $result) $output[$result['category']] = $result['count'];

        return $output;
    }


    public function fetch_sub_categories(string $category = ''): array
    {
        $where = (!empty($category)) ? "WHERE `category` = :category" : "";
        $sql = "SELECT `sub_category`, COUNT(*) as `count` FROM `babbler_entries` {$where} GROUP BY `sub_category`;";
        $params = (!empty($category)) ? ["category" => $category] : [];
        return ($this->query_execute(query: $sql, params: $params)) ? $this->fetch_all_results() : [];
    }


    public function fetch_entries_by_category(string $category, string $sub_category = '', string $order_by = 'created',
                                              string $sort = '', int $start = 0, int $limit = 10): array
    {
        $where = "`category` = :category" . (!(empty($sub_category)) ? " AND `sub_category` = :sub_category" : '');
//        $where .= " AND `published` IS NOT NULL AND `is_draft` = 0 AND `is_hidden` = 0"; // hidden for testing purposes
        $order_by = ($this->valid_schema(table: 'babbler_entries', column: $order_by)) ? $order_by : 'created';
        $sort = (in_array(needle: strtoupper($sort), haystack: ['ASC', 'DESC'])) ? strtoupper($sort) : 'ASC';

        $sql = "SELECT * FROM `babbler_entries` WHERE {$where} ORDER BY {$order_by} {$sort}";

        if (0 !== $limit) {
            $sql .= ($start < 0) ? " LIMIT {$limit};" : " LIMIT {$start}, {$limit};";
        } else {
            $sql .= ";";
        }

        $params = ['category' => $category];
        if (!empty($sub_category)) $params['sub_category'] = $sub_category;
        return ($this->query_execute($sql, $params)) ? $this->fetch_all_results() : [];
    }


    public function fetch_entry_by_category_and_title(string $category, string $title, string $sub_category = ''): array
    {
        $where = "WHERE `category` = :category" . ((!empty($sub_category)) ? " AND `sub_category` = :sub_category" : '');
        $sql = "SELECT * FROM `babbler_entries` {$where} AND `title` LIKE :title;";
        $params = ['category' => $category, 'title' => $title];
        if (!empty($sub_category)) $params['sub_category'] = $sub_category;
        return ($this->query_execute($sql, $params)) ? $this->fetch_all_results(true) : [];
    }
}
