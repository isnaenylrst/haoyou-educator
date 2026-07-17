<?php

namespace Database\Seeders;

use App\Models\PdfTemplate;
use Illuminate\Database\Seeder;

class PdfTemplateSeeder extends Seeder
{
    public function run(): void
    {
        PdfTemplate::insert([

            [

                'template_name'=>'Agreement Default',

                'category'=>'Agreement',

                'file_name'=>'agreement_default.pdf',

                'file_path'=>'templates/agreement_default.pdf',

                'version'=>'1.0',

                'is_active'=>true,

            ],

            [

                'template_name'=>'Invoice Default',

                'category'=>'Invoice',

                'file_name'=>'invoice_default.pdf',

                'file_path'=>'templates/invoice_default.pdf',

                'version'=>'1.0',

                'is_active'=>true,

            ],

            [

                'template_name'=>'Certificate Default',

                'category'=>'Certificate',

                'file_name'=>'certificate_default.pdf',

                'file_path'=>'templates/certificate_default.pdf',

                'version'=>'1.0',

                'is_active'=>true,

            ],

            [

                'template_name'=>'Report Default',

                'category'=>'Report',

                'file_name'=>'report_default.pdf',

                'file_path'=>'templates/report_default.pdf',

                'version'=>'1.0',

                'is_active'=>true,

            ],

        ]);
    }
}