<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Breadth pass over the skill taxonomy.
 *
 * SkillTaxonomySeeder covers the six job categories the app launched with. This
 * adds the domains real candidates type that those categories never touched —
 * creative and media (Blender, Unity, Premiere Pro), skilled trades,
 * hospitality, education, legal, retail, security, agriculture, aviation.
 *
 * Breadth matters more than depth for type-ahead: a candidate who types their
 * actual skill and gets no result concludes the app does not know their trade.
 * Clusters here are smaller and more loosely weighted than the core set, since
 * these are the long tail rather than the categories the product sells against.
 */
class SkillTaxonomyExpansionSeeder extends Seeder
{
    private const CLUSTERS = [
        // ── Creative, media, design ──────────────────────────────────────────
        ['Creative & Design', 82, ['Adobe Photoshop', 'Adobe Illustrator', 'Adobe InDesign', 'Graphic Design', 'Branding', 'Typography', 'Logo Design']],
        ['Creative & Design', 85, ['Premiere Pro', 'After Effects', 'Video Editing', 'Motion Graphics', 'DaVinci Resolve', 'Colour Grading', 'Final Cut Pro']],
        ['Creative & Design', 88, ['Blender', 'Maya', '3ds Max', '3D Modelling', 'Texturing', 'Rigging', 'Animation', 'Cinema 4D']],
        ['Creative & Design', 86, ['Unity', 'Unreal Engine', 'Game Development', 'Game Design', 'Level Design', 'C# Scripting', 'Shader Programming']],
        ['Creative & Design', 80, ['Photography', 'Videography', 'Lightroom', 'Photo Retouching', 'Studio Lighting', 'Drone Operation']],
        ['Creative & Design', 78, ['Content Writing', 'Copywriting', 'Scriptwriting', 'Proofreading', 'Editing', 'Technical Writing']],
        ['Creative & Design', 76, ['Social Media Management', 'Content Creation', 'Instagram Marketing', 'YouTube Management', 'Community Management']],
        ['Creative & Design', 80, ['Figma', 'Adobe XD', 'Sketch', 'Prototyping', 'Design Systems', 'User Research', 'Usability Testing']],

        // ── Skilled trades & construction ────────────────────────────────────
        ['Skilled Trades', 84, ['Welding', 'MIG Welding', 'TIG Welding', 'Arc Welding', 'Fabrication', 'Metal Cutting', 'Blueprint Reading']],
        ['Skilled Trades', 84, ['Electrician', 'Wiring', 'Circuit Testing', 'Conduit Installation', 'Electrical Safety']],
        ['Skilled Trades', 82, ['Plumbing', 'Pipe Fitting', 'Drainage', 'Sanitary Installation']],
        ['Skilled Trades', 82, ['Carpentry', 'Joinery', 'Furniture Assembly', 'Wood Finishing']],
        ['Skilled Trades', 80, ['Masonry', 'Plastering', 'Tiling', 'Painting and Decorating', 'Scaffolding']],
        ['Skilled Trades', 82, ['HVAC', 'Refrigeration', 'Air Conditioning Repair', 'Chiller Maintenance']],
        ['Skilled Trades', 80, ['CNC Machining', 'Lathe Operation', 'Milling', 'Tool and Die', 'Precision Measurement']],
        ['Skilled Trades', 80, ['Automobile Repair', 'Engine Diagnostics', 'Vehicle Servicing', 'Auto Electrical', 'Denting and Painting']],

        // ── Hospitality, food, retail ────────────────────────────────────────
        ['Hospitality', 84, ['Barista', 'Espresso Machine Operation', 'Latte Art', 'Coffee Brewing', 'Cafe Operations']],
        ['Hospitality', 84, ['Cooking', 'Food Preparation', 'Menu Planning', 'Kitchen Management', 'Food Safety', 'HACCP', 'Baking', 'Pastry']],
        ['Hospitality', 82, ['Food and Beverage Service', 'Waiting Tables', 'Bartending', 'Table Setting', 'Order Taking']],
        ['Hospitality', 80, ['Front Office', 'Guest Relations', 'Hotel Reception', 'Reservation Management', 'Concierge', 'Housekeeping']],
        ['Hospitality', 78, ['Event Management', 'Banquet Operations', 'Catering', 'Wedding Coordination']],
        ['Retail', 82, ['Retail Sales', 'Visual Merchandising', 'Stock Replenishment', 'Cash Handling', 'Point of Sale', 'Upselling', 'Store Operations']],

        // ── Education & training ─────────────────────────────────────────────
        ['Education', 84, ['Teaching', 'Lesson Planning', 'Classroom Management', 'Curriculum Development', 'Student Assessment']],
        ['Education', 80, ['Tutoring', 'Online Teaching', 'Special Education', 'Early Childhood Education', 'Montessori']],
        ['Education', 78, ['Corporate Training', 'Instructional Design', 'E-Learning', 'Training Delivery', 'Learning Management Systems']],

        // ── Legal, compliance, public sector ─────────────────────────────────
        ['Legal & Compliance', 82, ['Legal Research', 'Contract Drafting', 'Contract Review', 'Litigation Support', 'Legal Documentation']],
        ['Legal & Compliance', 80, ['Regulatory Compliance', 'Corporate Governance', 'Company Secretarial', 'Due Diligence', 'Policy Writing']],
        ['Legal & Compliance', 78, ['Intellectual Property', 'Trademark Filing', 'Data Protection', 'GDPR', 'PDPA']],

        // ── Security, facilities, transport ──────────────────────────────────
        ['Security & Facilities', 82, ['Security Operations', 'CCTV Monitoring', 'Access Control', 'Patrolling', 'Incident Reporting']],
        ['Security & Facilities', 80, ['Facility Management', 'Building Maintenance', 'Janitorial Services', 'Waste Management', 'Pest Control']],
        ['Transport', 82, ['Driving', 'Heavy Vehicle Licence', 'Delivery Driving', 'Route Optimisation', 'Vehicle Inspection', 'Defensive Driving']],
        ['Aviation', 80, ['Ground Handling', 'Cabin Crew', 'Aviation Safety', 'Ramp Operations', 'Baggage Handling', 'Airport Operations']],
        ['Maritime', 80, ['Marine Operations', 'Port Operations', 'Vessel Maintenance', 'Deck Operations', 'Marine Safety']],

        // ── Agriculture & environment ────────────────────────────────────────
        ['Agriculture', 80, ['Farming', 'Crop Management', 'Irrigation', 'Horticulture', 'Landscaping', 'Greenhouse Operations', 'Soil Testing']],
        ['Agriculture', 76, ['Animal Husbandry', 'Veterinary Assistance', 'Poultry Management', 'Dairy Operations']],

        // ── More IT depth candidates actually type ───────────────────────────
        ['IT & Software', 84, ['Flutter Web', 'State Management', 'Provider', 'Riverpod', 'BLoC', 'GetX']],
        ['IT & Software', 82, ['Vue.js', 'Angular', 'Svelte', 'Nuxt.js', 'Webpack', 'Vite']],
        ['IT & Software', 82, ['Laravel Livewire', 'Inertia.js', 'PHPUnit', 'Composer', 'Blade Templates']],
        ['IT & Software', 80, ['Power BI', 'Tableau', 'Looker', 'Data Visualisation', 'DAX', 'ETL', 'Data Warehousing']],
        ['IT & Software', 80, ['Linux', 'Bash Scripting', 'Nginx', 'Apache', 'System Administration', 'Shell Scripting']],
        ['IT & Software', 78, ['Salesforce Administration', 'SAP ABAP', 'Oracle', 'Dynamics 365', 'ERP Implementation']],
        ['IT & Software', 78, ['Technical Support', 'Helpdesk', 'Troubleshooting', 'Hardware Repair', 'Network Configuration', 'Active Directory']],
        ['IT & Software', 80, ['Prompt Engineering', 'LLM Integration', 'OpenAI API', 'RAG', 'Vector Databases', 'LangChain']],
        ['IT & Software', 78, ['Blockchain', 'Solidity', 'Smart Contracts', 'Web3']],
        ['IT & Software', 76, ['Business Analysis', 'Requirement Gathering', 'User Stories', 'Process Mapping', 'Stakeholder Management']],

        // ── Healthcare depth ─────────────────────────────────────────────────
        ['Healthcare & Nursing', 82, ['Pharmacy', 'Dispensing', 'Medication Counselling', 'Inventory of Drugs']],
        ['Healthcare & Nursing', 82, ['Radiography', 'X-Ray Operation', 'Ultrasound', 'CT Scan', 'MRI']],
        ['Healthcare & Nursing', 80, ['Dental Assistance', 'Oral Hygiene', 'Dental Charting']],
        ['Healthcare & Nursing', 80, ['Operating Theatre', 'Surgical Assistance', 'Sterilisation', 'Anaesthesia Support']],
        ['Healthcare & Nursing', 78, ['Mental Health Support', 'Counselling', 'Psychology', 'Case Management']],

        // ── Cross-cutting workplace ──────────────────────────────────────────
        ['Administration', 72, ['Google Workspace', 'Microsoft Word', 'PowerPoint', 'Outlook', 'Minute Taking', 'Travel Coordination']],
        ['Administration', 70, ['Multilingual', 'English', 'Mandarin', 'Malay', 'Tamil', 'Hindi', 'Translation', 'Interpretation']],
        ['Sales & Marketing', 78, ['Market Research', 'Competitor Analysis', 'Brand Management', 'Product Marketing', 'Campaign Management']],
        ['Sales & Marketing', 78, ['Telesales', 'Inside Sales', 'Field Sales', 'Channel Sales', 'Key Account Management', 'Negotiation']],
        ['Human Resources', 78, ['Payroll', 'Compensation and Benefits', 'Performance Management', 'Learning and Development', 'Labour Law']],
    ];

    /** Bridges into the core taxonomy so new clusters are not islands. */
    private const BRIDGES = [
        [['Blender', 'Maya', '3ds Max'], ['Unity', 'Unreal Engine', 'Animation'], 66],
        [['Unity', 'Unreal Engine'], ['C# Scripting', 'Game Development', 'Git'], 68],
        [['Premiere Pro', 'After Effects'], ['Video Editing', 'Motion Graphics', 'Photography'], 70],
        [['Figma', 'Adobe XD'], ['UI Design', 'UX Design', 'Prototyping'], 72],
        [['Barista', 'Cooking'], ['Customer Service', 'Food Safety', 'Cash Handling'], 62],
        [['Retail Sales'], ['Customer Service', 'Point of Sale', 'Visual Merchandising'], 65],
        [['Welding', 'CNC Machining'], ['Blueprint Reading', 'Quality Control', 'Workplace Safety'], 64],
        [['Driving', 'Delivery Driving'], ['Route Planning', 'Last Mile Delivery', 'Logistics Coordination'], 66],
        [['Teaching', 'Tutoring'], ['Lesson Planning', 'Communication', 'Classroom Management'], 68],
        [['Power BI', 'Tableau'], ['SQL', 'Data Science', 'Advanced Excel'], 70],
        [['Prompt Engineering', 'LLM Integration'], ['Python', 'REST API', 'Machine Learning'], 68],
        [['State Management', 'Provider', 'BLoC'], ['Flutter', 'Dart'], 78],
        [['Security Operations'], ['CCTV Monitoring', 'Incident Reporting', 'Workplace Safety'], 66],
        [['Pharmacy', 'Radiography'], ['Patient Care', 'Medical Records'], 64],
    ];

    private const POPULAR = [
        'English' => 96, 'Communication' => 94, 'Teaching' => 82,
        'Driving' => 82, 'Retail Sales' => 80, 'Cooking' => 79,
        'Adobe Photoshop' => 79, 'Video Editing' => 77, 'Welding' => 76,
        'Premiere Pro' => 75, 'Blender' => 72, 'Unity' => 71,
        'Figma' => 74, 'Power BI' => 73, 'Linux' => 72,
        'Security Operations' => 70, 'Payroll' => 70, 'Barista' => 66,
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $resolved = [];

            foreach (self::CLUSTERS as [$category, $weight, $names]) {
                $cluster = [];
                foreach ($names as $name) {
                    $skill = Skill::resolve($name, $category, curated: true);
                    $skill->forceFill([
                        'is_curated' => true,
                        // Never downgrade a core-seed skill's category or rank.
                        'category' => $skill->category ?: $category,
                        'popularity' => max(
                            $skill->popularity ?? 0,
                            self::POPULAR[$name] ?? 45
                        ),
                    ])->save();

                    $cluster[] = $skill;
                    $resolved[$name] = $skill;
                }

                foreach ($cluster as $i => $a) {
                    foreach (array_slice($cluster, $i + 1) as $b) {
                        Skill::relate($a, $b, $weight);
                    }
                }
            }

            foreach (self::BRIDGES as [$froms, $tos, $weight]) {
                foreach ($froms as $fromName) {
                    $from = $resolved[$fromName]
                        ?? Skill::firstWhere('slug', Skill::slugFor($fromName));
                    if (! $from) {
                        continue;
                    }
                    foreach ($tos as $toName) {
                        $to = $resolved[$toName]
                            ?? Skill::firstWhere('slug', Skill::slugFor($toName));
                        if ($to) {
                            Skill::relate($from, $to, $weight);
                        }
                    }
                }
            }
        });

        $this->command?->info(
            'Taxonomy now: '.Skill::count().' skills, '
            .DB::table('skill_relations')->count().' relations.'
        );
    }
}
