<?php

declare(strict_types=1);

namespace NestboxPHP\Babbler;

use NestboxPHP\Nestbox\Nestbox;
use NestboxPHP\Babbler\Exception\BabblerException;
use PDO;

class Babbler extends Nestbox
{
    final public const PACKAGE_NAME = 'babbler';

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
     * @return int|bool
     * @throws BabblerException
     */
    public function add_entry(string $category, string $subCategory, string $title, string $content, string $author,
                              string $created = null, string $published = null, bool $isDraft = false,
                              bool   $isHidden = false): int|bool
    {
        // prepare vars and verify entry
        $emptyStrings = [];
        if (0 == strlen(trim($category))) $emptyStrings[] = "'category'";
        if (0 == strlen(trim($subCategory))) $emptyStrings[] = "'sub_category'";
        if (0 == strlen(trim($title))) $emptyStrings[] = "'title'";
        if (0 == strlen(trim($content))) $emptyStrings[] = "'content'";
        if (0 == strlen(trim($author))) $emptyStrings[] = "'author'";
        $emptyStrings = join(", ", $emptyStrings);
        if ($emptyStrings) throw new BabblerException("Empty strings provided for: $emptyStrings");

        $params = [
            "category" => trim($category),
            "sub_category" => trim($subCategory),
            "title" => trim($title),
            "content" => trim($content),
            "dynamic_content" => $this->generate_dynamic_content(trim($content)),
            "created_by" => trim($author),
            "edited_by" => trim($author),
            "created" => date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: ($created ?? "now"))),
            "edited" => date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: ($created ?? "now"))),
            "published" => ($published) ? date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: $published)) : null,
            "is_draft" => $isDraft,
            "is_hidden" => $isHidden,
        ];

        // execute
        return $this->insert("babbler_entries", rows: $params);
    }


    /**
     * Edits an existing entry
     *
     * @param string|int $entryId
     * @param string $editor
     * @param string $category
     * @param string $subCategory
     * @param string $title
     * @param string $content
     * @param string $published
     * @param bool|null $isDraft
     * @param bool|null $isHidden
     * @return int|false
     */
    public function edit_entry(string|int $entryId, string $editor, string $category = "", string $subCategory = "",
                               string     $title = "", string $content = "", string $published = "",
                               bool       $isDraft = null, bool $isHidden = null): int|false
    {
        // verify entry data
        if (0 == intval($entryId)) {
            $this->err[] = "Missing data for entry: 'Entry ID'";
            return false;
        }

        $params = ["edited_by" => $editor];
        if (!empty(trim($category))) $params['category'] = trim($category);
        if (!empty(trim($subCategory))) $params['sub_category'] = trim($subCategory);
        if (!empty(trim($title))) $params['title'] = trim($title);
        if (!empty(trim($content))) $params['content'] = trim($content);
        if (!empty(trim($content))) $params['dynamic_content'] = $this->generate_dynamic_content(trim($content));
        if (preg_match('/\d[4]\-\d\d\-\d\d.\d\d(\:\d\d(\:\d\d)?)?/', $published, $t)) {
            $params["published"] = date(format: 'Y-m-d H:i:s', timestamp: strtotime(datetime: $published));
        }
        if (isset($isDraft)) $params["is_draft"] = $isDraft;
        if (isset($isHidden)) $params["is_hidden"] = $isHidden;

        // execute
        return $this->update("babbler_entries", updates: $params, where: ["entry_id" => $entryId]);
    }


    /**
     * Deletes an existing entry
     *
     * @param int $entryId
     * @return bool
     */
    public function delete_entry(int $entryId): bool
    {
        return $this->delete("babbler_entries", ["entry_id" => $entryId]);
    }


    /**
     * Dynamic Content
     */


    public function edit_dynamic_content_rule(string $pattern, string $replacement, int $orderId = 0): true
    {
        // pattern validation
//        if (!str_starts_with($pattern, "/")) $pattern = "/$pattern";

        $params = [
            "pattern" => $pattern,
            "replacement" => $replacement
        ];
        if (0 == $orderId) $params["order"] = $orderId;

        return false !== $this->insert("babbler_dynamic_content", $params);
    }


    public function reoder_dynamic_content_rules(array $order): true
    {
        $rules = $this->select("babbler_dynamic_content");
    }


    public function generate_dynamic_content(string $text): string
    {
        $rules = $this->select("babbler_dynamic_content");
        foreach ($rules as $rule) $text = preg_replace($rule["pattern"], $rule["replacement"], $text);
        return $text;
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
        $params = [];

        foreach (explode(" ", $this->sanitize_search_string($words)) as $word) {
            $word = preg_replace("/[^\w]+/", "", $word);

            $p = "word_" . strval(count($params));
            $params[$p] = $word;
            $cases[] = "CASE WHEN FIND_IN_SET(:$p, REPLACE(`content`, ' ', ',')) > 0 THEN 1 ELSE 0 END";
        }

        $cases = implode(" + ", $cases);

        $sql = "SELECT * FROM (SELECT *, ($cases) AS `threshold` FROM `babbler_entries`) AS `subquery`
                WHERE 0 < `threshold` ORDER BY `threshold` DESC;";

        if (!$this->query_execute($sql, $params)) return [];

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
                    `fronted_title` VARCHAR(255) AS (
                        CASE WHEN title LIKE 'The %'
                        THEN CONCAT(SUBSTRING(title, 5), ', The')
                        ELSE title END
                    ) STORED NOT NULL ,
                    `content` MEDIUMTEXT NOT NULL ,
                    `dynamic_content` MEDIUMTEXT NULL ,
                    PRIMARY KEY ( `entry_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";

        return $this->query_execute($sql);
    }


    public function create_class_table_babbler_history(): bool
    {
        // check for valid schemas
        if (!$this->valid_schema("babbler_entries")) $this->create_class_table_babbler_entries();
        if ($this->valid_schema("babbler_history") && $this->valid_trigger("babbler_entries", "babbler_history_trigger")) {
            return true;
        }

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
                    `dynamic_content` MEDIUMTEXT NULL ,
                    PRIMARY KEY ( `history_id` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";

        if (!$this->query_execute($sql)) return false;

        // create history trigger
        $sql = "CREATE TRIGGER IF NOT EXISTS `babbler_history_trigger` AFTER UPDATE ON `babbler_entries`\n" .
                "FOR EACH ROW\n" .
                "IF ( OLD.edited <> NEW.edited ) THEN\n" .
                "    INSERT INTO `babbler_history` (\n" .
                "        `entry_id` , \n" .
                "        `created` , \n" .
                "        `edited` , \n" .
                "        `published` , \n" .
                "        `is_draft` , \n" .
                "        `is_hidden` , \n" .
                "        `created_by` , \n" .
                "        `edited_by` , \n" .
                "        `category` , \n" .
                "        `sub_category` , \n" .
                "        `title` , \n" .
                "        `content`\n" .
                "    ) VALUES (\n" .
                "        OLD.`entry_id` , \n" .
                "        OLD.`created` , \n" .
                "        OLD.`edited` , \n" .
                "        OLD.`published` , \n" .
                "        OLD.`is_draft` , \n" .
                "        OLD.`is_hidden` , \n" .
                "        OLD.`created_by` , \n" .
                "        OLD.`edited_by` , \n" .
                "        OLD.`category` , \n" .
                "        OLD.`sub_category` , \n" .
                "        OLD.`title` , \n" .
                "        OLD.`content`\n" .
                "    );\n" .
                "END IF;\n" .
                "CREATE TRIGGER `babbler_delete_trigger` BEFORE DELETE\n" .
                "ON `babbler_entries` FOR EACH ROW\n" .
                "BEGIN\n" .
                "    INSERT INTO `babbler_history` (\n" .
                "        `entry_id` , \n" .
                "        `created` , \n" .
                "        `edited` , \n" .
                "        `published` , \n" .
                "        `is_draft` , \n" .
                "        `is_hidden` , \n" .
                "        `created_by` , \n" .
                "        `edited_by` , \n" .
                "        `category` , \n" .
                "        `sub_category` , \n" .
                "        `title` , \n" .
                "        `content` \n" .
                "    ) VALUES (\n" .
                "        OLD.`entry_id` , \n" .
                "        OLD.`created` , \n" .
                "        OLD.`edited` , \n" .
                "        OLD.`published` , \n" .
                "        OLD.`is_draft` , \n" .
                "        OLD.`is_hidden` , \n" .
                "        OLD.`created_by` , \n" .
                "        OLD.`edited_by` , \n" .
                "        OLD.`category` , \n" .
                "        OLD.`sub_category` , \n" .
                "        OLD.`title` , \n" .
                "        OLD.`content`\n" .
                "    );\n" .
                "END;";

        return $this->query_execute($sql);
    }


    public function create_class_table_babbler_dynamic_content(): bool
    {
        // check if entry table exists
        if ($this->valid_schema('babbler_dynamic_content')) return true;

        $sql = "CREATE TABLE IF NOT EXISTS `babbler_dynamic_content` (
                    `order` INT NOT NULL AUTO_INCREMENT ,
                    `pattern` VARCHAR( 512 ) UNIQUE NOT NULL ,
                    `replacement` VARCHAR( 4096 ) NOT NULL ,
                    PRIMARY KEY ( `order` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";

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

    // TODO: make orderBy an array so multiple columns can be sorted
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
        $sql = "SELECT `category`, COUNT(*) as `count`
                FROM `babbler_entries` 
                GROUP BY `category` 
                ORDER BY `category` ASC;";

        if (!$this->query_execute($sql)) return $output;

        return $this->fetch_all_results(fetchMode: PDO::FETCH_KEY_PAIR);
    }


    public function fetch_sub_categories(string $category = ""): array
    {
        $sql = "SELECT `sub_category`, COUNT(*) as `count`
                FROM `babbler_entries`
                WHERE `category` LIKE :category
                GROUP BY `sub_category`
                ORDER BY `sub_category` ASC;";

        $params = ["category" => (!empty(trim($category))) ? trim($category) : "%"];
        if (!$this->query_execute(query: $sql, params: $params)) return [];

        return $this->fetch_all_results(fetchMode: PDO::FETCH_KEY_PAIR);
    }

    // TODO: make orderBy an array so multiple columns can be sorted
    public function fetch_entries_by_category(string $category, string $subCategory = '', array $orderBy = [],
                                              int $start = 0, int $limit = 10, bool $showHidden = false): array|bool
    {
        $wheres = ($showHidden) ? [
            "published IS NOT" => null,
            "is_draft" => 0,
            "is_hidden" => 0,
        ] : [];
        $wheres["category LIKE"] = (trim($category)) ? trim($category) : "%";
        $wheres["sub_category LIKE"] = (trim($subCategory)) ? trim($subCategory) : "%";
        $orderBy = ($orderBy) ? $orderBy : ["fronted_title" => "ASC"];
        return $this->select("babbler_entries", where: $wheres, orderBy: $orderBy, limit: $limit, start: $start);
    }


    public function fetch_entry_by_category_and_title(string $category, string $title, string $sub_category = ''): array
    {
        $where = "WHERE `category` = :category" . ((!empty($sub_category)) ? " AND `sub_category` = :sub_category" : '');
        $sql = "SELECT * FROM `babbler_entries` {$where} AND `title` LIKE :title;";
        $params = ['category' => $category, 'title' => $title];
        if (!empty($sub_category)) $params['sub_category'] = $sub_category;
        return ($this->query_execute($sql, $params)) ? $this->fetch_first_result() : [];
    }
}
