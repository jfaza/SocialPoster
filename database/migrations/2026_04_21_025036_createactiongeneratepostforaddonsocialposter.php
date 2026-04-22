<?php

use ExpressionEngine\Service\Migration\Migration;

class Createactiongeneratepostforaddonsocialposter extends Migration
{
    /**
     * Execute the migration
     * @return void
     */
    public function up()
    {
        ee('Model')->make('Action', [
            'class' => 'Socialposter',
            'method' => 'GeneratePost',
            'csrf_exempt' => false,
        ])->save();
    }

    /**
     * Rollback the migration
     * @return void
     */
    public function down()
    {
        ee('Model')->get('Action')
            ->filter('class', 'Socialposter')
            ->filter('method', 'GeneratePost')
            ->delete();
    }
}
