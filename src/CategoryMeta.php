<?php

class CategoryMeta
{

    /**
     * CategoryMeta constructor.
     */
    public function __construct()
    {
        add_action('category_add_form_fields', [$this, 'categoryMeta']);
    }

    public function categoryMeta()
    {
        ?>
        <div class="form-field term-group">
            <label><?php _e('Site Replication Source', 'replica_demo'); ?>
                <input type="url" name="source-url" value=""/>
            </label>
        </div>
    <?php
    }
}