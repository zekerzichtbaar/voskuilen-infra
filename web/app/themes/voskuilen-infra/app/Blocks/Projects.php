<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use StoutLogic\AcfBuilder\FieldsBuilder;

class Projects extends Block
{
    public $name = 'Projects';
    public $description = 'A simple Projecten block.';
    public $category = 'formatting';
    public $icon = '<svg viewBox="0 0 115.71 97.19"><path fill="#ff0080" d="M727.75,352.3c-13.19-11.19-32.49-20.86-56.61-19-2.7.21-5.43.52-8.18.95-42.16,6.51-45.84,58.68-21.93,83.27,13.21,13.58,34.83,18.76,63.05,3.18A123.76,123.76,0,0,0,725,406.47c24.81-21.39,19.51-40,2.74-54.17" transform="translate(-626.28 -333.06)"/><path fill="#fff" d="M701.41,387.75c-4.48-3.14-12.36-.15-17.28.54,6.29-6.05,23.08-12.65,16.49-20.34-7.77-9.44-30.26-6.8-35-1.11-1.77,2.14-2.22,6.11.56,7.78,5.55,3.88,17.93-.56,17.93-.56-7.21,6.11-22.93,11.66-19,20.55,1.86,4,6.28,6,11.6,6.58,9.73,1.6,22-1.69,25.27-5.67,1.78-2.14,2.23-6.11-.55-7.77" transform="translate(-626.28 -333.06)"/></svg>';
    public $keywords = [];
    public $post_types = [];
    public $parent = [];
    public $mode = 'edit';

    public $supports = [
        'full_height' => false,
        'anchor' => false,
        'mode' => 'edit',
        'multiple' => true,
        'supports' => array('mode' => false),
        'jsx' => true,
    ];

    public function with()
    {
        $project_count = get_field('project_count');
        $project_query_args = ['post_type' => 'project', 'orderby' => 'date', 'order' => 'DESC', 'posts_per_page' => $project_count];

        
        if(!empty($category = get_field('project_category'))) {
            $project_query_args['tax_query'] = [[
                'taxonomy' => 'project_category',
                'terms' => $category
            ]];
        }

        $projects = new \WP_Query($project_query_args);

        return [
            'pt'                      => get_field('padding_top'),
            'pb'                      => get_field('padding_bottom'),
            'background'              => get_field('background'),
            'title'                   => get_field('title'),
            'heading' => get_field('heading'),
            'project_heading' => get_field('project_heading'),
            'projects' => $projects,
            'project_count' => $project_count,
            'position'              => [
                    'md:col-span-5',
                    'md:col-span-7',
                    'md:col-span-6',
                    'md:col-span-6',
                    'md:col-span-7',
                    'md:col-span-5',
            ]
        ];
    }

    public function fields()
    {
        $acfFields = new FieldsBuilder('projects');

        return $acfFields->build();
    }

    public function enqueue()
    {
        //
    }
}
