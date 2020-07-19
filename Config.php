<?php
namespace Plugin\cms;

use \Plugin\BasePluginConfig;
use \Plugin\cms\Services\ObjectTypesService;
use \Plugin\cms\Services\TaxonomiesService;
use \App\Models\RoleModel;
use \App\Entities\Role;
use \App\Models\CapabilityModel;
use CodeIgniter\I18n\Time;

class Config extends BasePluginConfig
{
    protected $name = "Syga CMS";
    protected $description = "Système de publication de contenus";
    protected $version = "1.0.0-alpha.1";
    protected $author = "Syga Technology Team Developer";
    protected $helpers = [
        'functions',
        'post',
        'term',
    ];

    public function pluginDidInstall()
    {
        $this->createTables();
        $this->registerOptions();
        $this->registerUserRoles();
        $this->registerCapabilities();
        $this->registerRoleCapabilities();

        $this->createTermsAndPostsAndSetDefaultCategoryOption();
    }

    public function pluginDidUninstall()
    {
        $this->deleteOptions();
        $this->deleteTables();
        $this->deleteUserRoles();
        $this->deleteCapabilities();
        $this->deleteRoleCapabilities();
    }

    private function createTables()
    {
        // Posts table
        $postForge = \Config\Database::forge();
        $postFields = [
            'post_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'post_author' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 0,
            ],
            'post_date DATETIME DEFAULT CURRENT_TIMESTAMP',
            'post_content' => [
                'type' => 'LONGTEXT',
                'collate' => 'utf8mb4_unicode_ci'
            ],
            'post_excerpt' => [
                'type' => 'TEXT',
                'collate' => 'utf8mb4_unicode_ci'
            ],
            'post_title' => [
                'type' => 'TEXT',
                'collate' => 'utf8mb4_unicode_ci'
            ],
            'post_status' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'collate' => 'utf8mb4_unicode_ci',
                'default' => 'publish',
            ],
            'comment_status' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'collate' => 'utf8mb4_unicode_ci',
                'default' => 'open',
            ],
            'post_name' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'collate' => 'utf8mb4_unicode_ci',
                'default' => '',
            ],
            'post_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'post_content_filtered' => [
                'type' => 'LONGTEXT',
                'collate' => 'utf8mb4_unicode_ci'
            ],
            'post_parent' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 0,
            ],
            'post_type' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'collate' => 'utf8mb4_unicode_ci',
                'default' => 'post',
            ],
            'comment_count' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'default' => 0,
            ],
            'post_deleted' => [
                'type' => 'DATETIME'
            ],
        ];
        $postForge->addField($postFields);
        $postForge->addKey('post_id', TRUE);
        $postForge->addKey('post_name');
        $postForge->addKey(['post_type','post_status','post_date','post_id']);
        $postForge->addKey('post_parent');
        $postForge->addKey('post_author');
        $postForge->dropTable('posts', TRUE);
        $postForge->createTable('posts');

        // Post meta table
        $postMetaForge = \Config\Database::forge();
        $postMetafields = [
            'meta_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'post_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true
            ],
            'meta_key' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'collate' => 'utf8mb4_unicode_ci',
                'null' => true
            ],
            'meta_value' => [
                'type' => 'LONGTEXT',
                'collate' => 'utf8mb4_unicode_ci',
                'null' => true
            ]
        ];
        $postMetaForge->addField($postMetafields);
        $postMetaForge->addKey('meta_id', TRUE);
        $postMetaForge->addKey('post_id');
        $postMetaForge->addKey('meta_key');
        $postMetaForge->dropTable('postmeta', TRUE);
        $postMetaForge->createTable('postmeta');

        // Terms table
        $termForge = \Config\Database::forge();
        $termfields = [
            'term_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'collate' => 'utf8mb4_unicode_ci',
                'default' => ''
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'collate' => 'utf8mb4_unicode_ci',
                'default' => ''
            ]
        ];
        $termForge->addField($termfields);
        $termForge->addKey('term_id', TRUE);
        $termForge->addKey('name');
        $termForge->addKey('slug');
        $termForge->dropTable('terms', TRUE);
        $termForge->createTable('terms');

        // Term Taxonomies table
        $termTaxonomiesForge = \Config\Database::forge();
        $termTaxonomiesfields = [
            'term_taxonomy_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'term_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 0,
            ],
            'taxonomy' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'collate' => 'utf8mb4_unicode_ci',
                'default' => ''
            ],
            'description' => [
                'type' => 'LONGTEXT',
                'collate' => 'utf8mb4_unicode_ci'
            ],
            'parent' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default' => 0,
            ],
            'count' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'default' => 0
            ]
        ];
        $termTaxonomiesForge->addField($termTaxonomiesfields);
        $termTaxonomiesForge->addKey('term_taxonomy_id', TRUE);
        $termTaxonomiesForge->addKey('term_id');
        $termTaxonomiesForge->addKey('taxonomy');
        $termTaxonomiesForge->dropTable('term_taxonomies', TRUE);
        $termTaxonomiesForge->createTable('term_taxonomies');

        // term meta table
        $termMetaForge = \Config\Database::forge();
        $termMetaFields = [
            'meta_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'term_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true
            ],
            'meta_key' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'collate' => 'utf8mb4_unicode_ci',
                'null' => true
            ],
            'meta_value' => [
                'type' => 'LONGTEXT',
                'collate' => 'utf8mb4_unicode_ci',
                'null' => true
            ]
        ];
        $termMetaForge->addField($termMetaFields);
        $termMetaForge->addKey('meta_id', TRUE);
        $termMetaForge->addKey('term_id');
        $termMetaForge->addKey('meta_key');
        $termMetaForge->dropTable('termmeta', TRUE);
        $termMetaForge->createTable('termmeta');

        // Term Relationships table
        $termRelationshipsForge = \Config\Database::forge();
        $termRelationships = [
            'object_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true
            ],
            'term_taxonomy_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true
            ],
            'term_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0
            ]
        ];
        $termRelationshipsForge->addField($termRelationships);
        $termRelationshipsForge->addKey('object_id', TRUE);
        $termRelationshipsForge->addKey('term_taxonomy_id', TRUE);
        $termRelationshipsForge->dropTable('term_relationships', TRUE);
        $termRelationshipsForge->createTable('term_relationships');
    }

    private function registerOptions()
    {
        $optionObject = \Config\Services::options();
        foreach ([
            'cms_default_comment_status' => [
                'post' => 'open',
                'page' => 'closed'
            ],
            'cms_default_category' => 'non-assignee'
        ] as $name => $value) {
            $optionObject->add($name, $value, true);
        }
    }

    private function registerUserRoles(){
        $roles = [
            [
                "label" => "Éditeur",
                "slug" => "editor"
            ],
            [
                "label" => "Contributeur",
                "slug" => "contributor"
            ],
            [
                "label" => "Membre",
                "slug" => "member",
                "default" => 1
            ]
        ];
        foreach ($roles as $data) {
            $role = new Role();
            $role->fill($data);
            $roleModel = new RoleModel();
            if(! $roleModel->exists($role->getSlug())){
                if($role->isDefauult()){
                    $db = \Config\Database::connect();
                    $db->table('roles')->where('is_default', 1)->update(['is_default' => 0]);
                }
                $roleModel->insert($role);
            }
        }
    }

    private function registerCapabilities(){
        $roleCapabilities = [
            [
                "slug" => "edit_post",
                "label" => "Éditer un poste"
            ],
            [
                "slug" => "delete_post",
                "label" => "Supprimer un poste"
            ],
            [
                "slug" => "publish_posts",
                "label" => "Pulblier des postes"
            ],
            [
                "slug" => "edit_term",
                "label" => "Éditer un terme"
            ],
            [
                "slug" => "delete_term",
                "label" => "Supprimer un terme"
            ]
        ];
        $capabilityModel = new CapabilityModel();
        $capabilityModel->insertBatch($roleCapabilities);
    }

    private function registerRoleCapabilities(){
        $roleCapabilities = [
            "editor" => [
                "edit_post",
                "delete_post",
                "publish_posts",
                "edit_term",
                "delete_term"
            ],
            "contributor" => [
                "read_post",
                "edit_post",
                "edit_term",
            ]
        ];
        $data = [];
        foreach ($roleCapabilities as $role => $caps) {
            foreach ($caps as $cap) {
                $data[] = [
                    'role_slug' => $role,
                    'capability_slug' => $cap
                ];
            }
        }
        $db = \Config\Database::connect();
        $db->table('role_capabilities')->insertBatch($data);
    }

    private function createTermsAndPostsAndSetDefaultCategoryOption(){
        $db = \Config\Database::connect();
        $termBuilder = $db->table('terms');

        $categoryData = [
            "name" => "Non classée",
            "slug" => "non-classee"
        ];
        $termBuilder->insert($categoryData);
        $categoryId = $db->insertID();

        $termTaxBuilder = $db->table('term_taxonomies');
        $categoryTaxData = [
            "term_id" => $categoryId,
            "taxonomy" => "category",
            "description" => "Catégorie par défaut si un poste n'en possède pas",
            "parent" => 0,
            "count" => 0
        ];
        $termTaxBuilder->insert($categoryTaxData);
        $termTaxonomyId = $db->insertID();

        $tagData = [
            "name" => "Test étiquette",
            "slug" => "test-etiquette"
        ];
        $termBuilder->insert($tagData);
        $tagId = $db->insertID();
        $tagTaxData = [
            "term_id" => $tagId,
            "taxonomy" => "tag",
            "description" => "Exemple d'étiquette",
            "parent" => 0,
            "count" => 0
        ];
        $termTaxBuilder->insert($tagTaxData);

        $optionObject = \Config\Services::options();
        $optionObject->add('cms_default_category', $termTaxonomyId, true);

        $this->createPosts($termTaxonomyId);
    }

    private function createPosts($termTaxonomyId){
        $date = (new Time('now'))->toDateTimeString();
        $postData = array(
            'post_author'           => \Config\Services::currentUser()->getId(),
            'post_content'          => "<div><h2>Exemple d'article.</h2> <p>Vous pouvez modier cet article à tout moment dans l'espace administarateur.</p></div>",
            'post_excerpt'          => 'Une petite description',
            'post_content_filtered' => "Exemple d'article. Vous pouvez modier cet article à tout moment dans l'espace administarateur.",
            'post_title'            => 'Exemple d\'article',
            'post_status'           => 'publish',
            'post_type'             => 'post',
            'comment_status'        => 'open',
            'post_parent'           => 0,
            'post_date'             => $date,
            'post_modified'         => $date,
            'post_deleted'          => '0000-00-00 00:00:00'
        );
        $db = \Config\Database::connect();
        $postBuilder = $db->table('posts');
        $postBuilder->insert($postData);
        $postId = $db->insertID();
        $relationshipBuilder = $db->table('term_relationships');
        $relationshipBuilder->insert([
            'object_id' => $postId,
            'term_taxonomy_id' => $termTaxonomyId,
            'term_order' => 0
        ]);
        $pageData = array(
            'post_author'           => \Config\Services::currentUser()->getId(),
            'post_content'          => "<div><h3>Exemple de page.</h3> <p>Vous pouvez modier cette page à tout moment dans l'espace administarateur.</p></div>",
            'post_excerpt'          => 'Une petite description',
            'post_content_filtered' => "Exemple de page. Vous pouvez modier cette page à tout moment dans l'espace administarateur.",
            'post_title'            => 'Exemple de page',
            'post_status'           => 'publish',
            'post_type'             => 'page',
            'comment_status'        => 'closed',
            'post_parent'           => 0,
            'post_date'             => $date,
            'post_modified'         => $date,
            'post_deleted'          => '0000-00-00 00:00:00'
        );
        $postBuilder->insert($pageData);
    }

    private function deleteOptions()
    {
        $names = [
            'cms_default_comment_status',
            'cms_default_category'
        ];
        $optionObject = \Config\Services::options();
        $optionObject->delete($names);
    }

    private function deleteTables(){
        $forge = \Config\Database::forge();
        $forge->dropTable('posts', TRUE);
        $forge->dropTable('postmeta', TRUE);
        $forge->dropTable('terms', TRUE);
        $forge->dropTable('termmeta', TRUE);
        $forge->dropTable('term_relationships', TRUE);
        $forge->dropTable('term_taxonomies', TRUE);
    }

    private function deleteUserRoles(){
        $roles = [
            "editor",
            "contributor",
            "member"
        ];
        $db = \Config\Database::connect();
        $db->table('roles')->whereIn("slug", $roles)->delete();
    }

    private function deleteCapabilities(){
        $caps = [
            "edit_post",
            "delete_post",
            "publish_posts",
            "edit_term",
            "delete_term"
        ];
        $db = \Config\Database::connect();
        $db->table('capabilities')->whereIn("slug", $caps)->delete();
    }

    private function deleteRoleCapabilities(){
        $roles = [
            "editor",
            "contributor",
            "member"
        ];
        $db = \Config\Database::connect();
        $db->table('role_capabilities')->whereIn("role_slug", $roles)->delete();
    }

    public function pluginDidMount()
    {
        $this->registerTaxonomies();
        $this->registerObjectTypes();
    }

    private function registerTaxonomies()
    {
        TaxonomiesService::register('category', array('post'), array(
            'hierarchical' => true,
        ));
        TaxonomiesService::register('tag', array('post'), array(
            'hierarchical' => false,
        ));
    }

    private function registerObjectTypes()
    {
        ObjectTypesService::register('post', array(
            'hierarchical' => false,
            'supports' => [
                'title' => true,
                'editor' => true,
                'category' => true,
                'tag' => false,
            ],
            'capability_type' => 'post',
        ));
        ObjectTypesService::register('page', array(
            'hierarchical' => true,
            'capability_type' => 'page',
        ));
    }

}
