<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Curated skill taxonomy for the Lucky Boss markets (SG / MY / IN).
 *
 * Each cluster is a set of skills that genuinely co-occur on real candidate
 * profiles, so selecting one surfaces the rest. That is the behaviour Naukri
 * gets from a taxonomy built over years of posting data; this is a deliberate,
 * smaller starting point covering the six job categories the app actually
 * serves, with AI expansion (SkillController) filling the gaps at runtime.
 *
 * Clusters are intra-linked at [weight], and skills are additionally given
 * cross-cluster links where the overlap is real — a Flutter developer plausibly
 * knows Git and REST APIs, which live in a different cluster.
 *
 * Idempotent: re-running updates rather than duplicating.
 */
class SkillTaxonomySeeder extends Seeder
{
    /**
     * [category, weight, [skills...]]
     *
     * Weight expresses how tightly the cluster hangs together. Dart and Flutter
     * are near-inseparable (90); "office skills" co-occur loosely (55).
     */
    private const CLUSTERS = [
        // ── IT & Software ────────────────────────────────────────────────────
        ['IT & Software', 90, ['Flutter', 'Dart', 'Mobile App Development', 'Android', 'iOS', 'Kotlin', 'Swift', 'React Native']],
        ['IT & Software', 85, ['React', 'JavaScript', 'TypeScript', 'Next.js', 'Redux', 'HTML', 'CSS', 'Tailwind CSS']],
        ['IT & Software', 85, ['Node.js', 'Express.js', 'REST API', 'GraphQL', 'Microservices']],
        ['IT & Software', 85, ['Python', 'Django', 'FastAPI', 'Flask', 'Pandas', 'NumPy']],
        ['IT & Software', 88, ['Machine Learning', 'Deep Learning', 'TensorFlow', 'PyTorch', 'Data Science', 'NLP', 'Computer Vision']],
        ['IT & Software', 80, ['Java', 'Spring Boot', 'Hibernate', 'Maven', 'JUnit']],
        ['IT & Software', 80, ['PHP', 'Laravel', 'MySQL', 'Blade', 'Eloquent ORM']],
        ['IT & Software', 82, ['Docker', 'Kubernetes', 'CI/CD', 'Jenkins', 'Terraform', 'DevOps']],
        ['IT & Software', 82, ['AWS', 'Azure', 'Google Cloud Platform', 'Cloud Architecture', 'Serverless']],
        ['IT & Software', 78, ['PostgreSQL', 'MongoDB', 'Redis', 'SQL', 'Database Design']],
        ['IT & Software', 75, ['Git', 'GitHub', 'Agile', 'Scrum', 'Jira']],
        ['IT & Software', 80, ['Manual Testing', 'Automation Testing', 'Selenium', 'Test Cases', 'QA']],
        ['IT & Software', 78, ['Cyber Security', 'Network Security', 'Penetration Testing', 'Firewall', 'SIEM']],
        ['IT & Software', 76, ['UI Design', 'UX Design', 'Figma', 'Wireframing', 'Prototyping', 'Adobe XD']],

        // ── Logistics & Warehouse ────────────────────────────────────────────
        ['Logistics & Warehouse', 85, ['Warehouse Operations', 'Inventory Management', 'Stock Control', 'Picking and Packing', 'Goods Receiving']],
        ['Logistics & Warehouse', 85, ['Supply Chain', 'Logistics Coordination', 'Freight Forwarding', 'Shipping Documentation', 'Customs Clearance']],
        ['Logistics & Warehouse', 82, ['Forklift Operator', 'Reach Truck', 'Pallet Jack', 'Material Handling', 'Forklift Licence']],
        ['Logistics & Warehouse', 78, ['WMS', 'SAP MM', 'Barcode Scanning', 'Cycle Counting']],
        ['Logistics & Warehouse', 75, ['Route Planning', 'Fleet Management', 'Last Mile Delivery', 'Dispatch']],

        // ── Engineering & Tech ───────────────────────────────────────────────
        ['Engineering & Tech', 85, ['AutoCAD', 'SolidWorks', 'Mechanical Design', '3D Modelling', 'GD&T']],
        ['Engineering & Tech', 85, ['Civil Engineering', 'Structural Analysis', 'Site Supervision', 'Quantity Surveying', 'STAAD Pro']],
        ['Engineering & Tech', 82, ['Electrical Maintenance', 'PLC Programming', 'SCADA', 'Instrumentation', 'Control Panels']],
        ['Engineering & Tech', 80, ['Preventive Maintenance', 'Troubleshooting', 'Root Cause Analysis', 'Machine Operation']],
        ['Engineering & Tech', 78, ['Quality Control', 'ISO 9001', 'Six Sigma', 'Lean Manufacturing', 'Kaizen']],
        ['Engineering & Tech', 76, ['HSE', 'Workplace Safety', 'Risk Assessment', 'Safety Audit']],

        // ── Healthcare & Nursing ─────────────────────────────────────────────
        ['Healthcare & Nursing', 88, ['Patient Care', 'Clinical Nursing', 'Vital Signs Monitoring', 'Medication Administration', 'Wound Care']],
        ['Healthcare & Nursing', 85, ['ICU', 'Emergency Care', 'BLS', 'ACLS', 'Triage']],
        ['Healthcare & Nursing', 80, ['Medical Records', 'Electronic Health Records', 'Patient Scheduling', 'Insurance Claims']],
        ['Healthcare & Nursing', 78, ['Phlebotomy', 'Laboratory Testing', 'Specimen Collection', 'Infection Control']],
        ['Healthcare & Nursing', 76, ['Elderly Care', 'Home Nursing', 'Rehabilitation Support', 'Physiotherapy Assistance']],

        // ── Finance & Banking ────────────────────────────────────────────────
        ['Finance & Banking', 85, ['Accounts Payable', 'Accounts Receivable', 'Bank Reconciliation', 'General Ledger', 'Bookkeeping']],
        ['Finance & Banking', 85, ['Financial Reporting', 'Financial Analysis', 'Budgeting', 'Forecasting', 'Variance Analysis']],
        ['Finance & Banking', 82, ['Tally', 'SAP FICO', 'QuickBooks', 'Xero', 'Advanced Excel']],
        ['Finance & Banking', 82, ['GST', 'Income Tax', 'TDS', 'Statutory Compliance', 'Audit']],
        ['Finance & Banking', 80, ['Credit Analysis', 'Risk Management', 'Loan Processing', 'KYC', 'AML']],
        ['Finance & Banking', 78, ['Payroll Processing', 'Invoice Processing', 'Petty Cash', 'Expense Management']],

        // ── Cross-cutting: sales, admin, service ─────────────────────────────
        ['Sales & Marketing', 82, ['Sales', 'Business Development', 'Lead Generation', 'Cold Calling', 'CRM', 'Salesforce']],
        ['Sales & Marketing', 80, ['Digital Marketing', 'SEO', 'Social Media Marketing', 'Google Ads', 'Content Marketing', 'Email Marketing']],
        ['Customer Service', 82, ['Customer Service', 'Customer Support', 'Call Centre', 'Complaint Handling', 'After Sales Service']],
        ['Administration', 70, ['MS Office', 'Microsoft Excel', 'Data Entry', 'Documentation', 'Report Preparation', 'Calendar Management']],
        ['Human Resources', 80, ['Recruitment', 'Talent Acquisition', 'Onboarding', 'Employee Relations', 'HRIS', 'Interviewing']],
    ];

    /**
     * Links that cross cluster boundaries. Weighted lower than intra-cluster
     * links so a genuine sibling always outranks a plausible neighbour.
     */
    private const BRIDGES = [
        [['Flutter', 'React Native', 'Android', 'iOS'], ['Git', 'REST API', 'Firebase'], 60],
        [['React', 'Next.js', 'JavaScript'], ['Node.js', 'REST API', 'Git'], 65],
        [['Python', 'Django', 'FastAPI'], ['PostgreSQL', 'Docker', 'REST API'], 62],
        [['Machine Learning', 'Data Science'], ['Python', 'Pandas', 'SQL'], 70],
        [['DevOps', 'Docker', 'Kubernetes'], ['AWS', 'CI/CD', 'Git'], 68],
        [['Laravel', 'PHP'], ['MySQL', 'REST API', 'Git'], 65],
        [['Warehouse Operations', 'Inventory Management'], ['Forklift Operator', 'WMS', 'Microsoft Excel'], 60],
        [['Supply Chain', 'Logistics Coordination'], ['Inventory Management', 'SAP MM', 'Advanced Excel'], 62],
        [['Financial Reporting', 'Financial Analysis'], ['Advanced Excel', 'Audit', 'Budgeting'], 64],
        [['Recruitment', 'Talent Acquisition'], ['Interviewing', 'MS Office', 'Customer Service'], 55],
        [['Clinical Nursing', 'Patient Care'], ['BLS', 'Medical Records', 'Infection Control'], 66],
        [['Sales', 'Business Development'], ['CRM', 'Customer Service', 'Lead Generation'], 63],
        [['UI Design', 'UX Design'], ['Figma', 'HTML', 'CSS'], 60],
    ];

    /**
     * Rough popularity ranking so type-ahead puts the common skill first.
     * Everything unlisted keeps a mid default.
     */
    private const POPULAR = [
        'Microsoft Excel' => 100, 'MS Office' => 98, 'Customer Service' => 95,
        'Communication' => 94, 'Sales' => 92, 'Data Entry' => 90,
        'Python' => 88, 'JavaScript' => 87, 'SQL' => 86, 'Java' => 85,
        'React' => 84, 'Git' => 83, 'Node.js' => 80, 'Flutter' => 78,
        'Warehouse Operations' => 78, 'Inventory Management' => 77,
        'Patient Care' => 76, 'Accounts Payable' => 75, 'Recruitment' => 74,
        'AutoCAD' => 73, 'Digital Marketing' => 72, 'Advanced Excel' => 72,
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
                        'category' => $category,
                        'is_curated' => true,
                        'popularity' => self::POPULAR[$name] ?? 50,
                    ])->save();

                    $cluster[] = $skill;
                    $resolved[$name] = $skill;
                }

                // Fully connect the cluster. These sets are small (4-8), so the
                // pair count stays trivial and every member suggests every other.
                foreach ($cluster as $i => $a) {
                    foreach (array_slice($cluster, $i + 1) as $b) {
                        Skill::relate($a, $b, $weight);
                    }
                }
            }

            foreach (self::BRIDGES as [$froms, $tos, $weight]) {
                foreach ($froms as $fromName) {
                    $from = $resolved[$fromName] ?? null;
                    if (! $from) {
                        continue;
                    }
                    foreach ($tos as $toName) {
                        $to = $resolved[$toName] ?? null;
                        if ($to) {
                            Skill::relate($from, $to, $weight);
                        }
                    }
                }
            }
        });

        $this->command?->info(
            'Skill taxonomy: '.Skill::count().' skills, '
            .DB::table('skill_relations')->count().' relations.'
        );
    }
}
