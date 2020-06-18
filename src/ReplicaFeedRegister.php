<?php

class ReplicaFeedRegister
{
    /** @var string */
    public $sourceUrlDescription;
    /**
     * CategoryMeta constructor.
     */
    public function __construct()
    {
        $this->sourceUrlDescription = __('URL of a WordPress site with REST API to pull posts from', 'replica_demo');
        add_action('init', [$this, 'registerReplicaTaxonomy'], 20);
        add_action('replica_add_form_fields', [$this, 'replicaMeta']);
        add_action('replica_edit_form_fields', [$this, 'editReplicaMeta'], 10);
        add_action('created_replica', [$this, 'saveReplicaMeta']);
        add_action('edited_replica', [$this, 'updateReplicaMeta'], 10);
    }

    public function replicaMeta()
    {
        ?>
        <div class="form-field term-group">
            <label for="source-url">
                <?php _e('Site Replication Source', 'replica_demo'); ?>
            </label>
            <input type="url" id="source_url" name="source_url" value=""/>
            <p class="description"><?= $this->sourceUrlDescription ?></p>
        </div>
        <?php
    }

    public function editReplicaMeta( $term ){
        $source_url = get_term_meta( $term->term_id, 'source_url', true );
        ?>
        <tr class="form-field term-group-wrap">
        <th scope="row">
            <label for="source-url">
                <?php _e('Site Replication Source', 'replica_demo'); ?>
            </label>
        </th>
        <td>
            <input type="url" id="source_url" name="source_url" value="<?= $source_url ?>"/>
            <p class="description"><?= $this->sourceUrlDescription ?></p>
        </td>
        </tr>
        <?php
    }

    public function saveReplicaMeta( $term_id ){
        if( isset( $_POST['source_url'] ) && '' !== $_POST['source_url'] ){
            $url = esc_url( $_POST['source_url'], ['http', 'https'] );
            add_term_meta( $term_id, 'source_url', $url, true );
        }
    }

    public function updateReplicaMeta( $term_id ){
        if( isset( $_POST['source_url'] ) && '' !== $_POST['source_url'] ){
            $url = esc_url( $_POST['source_url'], ['http', 'https'] );
            update_term_meta( $term_id, 'source_url', $url );
        }
    }

    public function registerReplicaTaxonomy()
    {
        $labels = [
          'name' => _x('Replicas', 'taxonomy general name', 'replica_demo'),
          'singular_name' => _x('Replica', 'taxonomy singular name', 'replica_demo'),
          'search_items' => __('Search Replica', 'replica_demo'),
          'popular_items' => __('Common Replicas', 'replica_demo'),
          'all_items' => __('All Replicas', 'replica_demo'),
          'edit_item' => __('Edit Replica', 'replica_demo'),
          'update_item' => __('Update Replica', 'replica_demo'),
          'add_new_item' => __('Add new Replica', 'replica_demo'),
          'new_item_name' => __('New Replica:', 'replica_demo'),
          'add_or_remove_items' => __('Remove Replica', 'replica_demo'),
          'choose_from_most_used' => __('Choose from common Replica', 'replica_demo'),
          'not_found' => __('No Replicas found.', 'replica_demo'),
          'menu_name' => __('Replica', 'replica_demo'),
        ];

        $args = [
            'hierarchical' => false,
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
        ];

        register_taxonomy('replica', ['post'], $args);
        register_term_meta('replica', 'source_url', [
            'type' => 'string',
            'description' => $this->sourceUrlDescription,
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'type' => 'string',
                    'format' => 'url',
                    'context' => ['view', 'edit'],
                    'readonly' => false,
                ]
            ],
        ]);
    }
}
