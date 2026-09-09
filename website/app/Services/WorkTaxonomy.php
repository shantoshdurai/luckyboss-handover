<?php

namespace App\Services;

/**
 * The trade vocabulary, ported from the Flutter app's `AppData`.
 *
 * Fourteen categories, 119 roles, and the abilities and certificates that
 * actually belong to each of them. Generated from
 * `luckyboss_jobseeker/lib/core/constants/app_data.dart`, which stays the
 * source of truth — regenerate rather than editing by hand, or the app and the
 * website start offering candidates different words for the same job.
 *
 * Why this exists: Lucky AI was building its options out of whatever vacancies
 * happened to be published, so a candidate was offered "Healthcare" and then
 * "Healthcare Assistant" — the job titles we had, not the work people do. The
 * app has always known better. A mason picks Construction, then Mason, then
 * taps Brickwork and Plastering from his own trade's vocabulary, and none of it
 * depends on whether we currently have a mason vacancy open.
 *
 * The ordering here is the app's, faithfully — Construction, then IT & Software,
 * then the rest. It is deliberately *not* the order the website leads with: spec
 * §58/§59 wants field trades first, and IT sitting second would quietly make
 * Lucky Boss an IT job board. That running order lives in `JobCategorySeeder`,
 * where changing it cannot make the app and the website disagree about what a
 * category is called.
 */
class WorkTaxonomy
{
    /**
     * @return list<array{name:string, icon:string, path:string, roles:list<array{name:string, abilities:list<string>, certificates:list<string>}>}>
     */
    public function categories(): array
    {
        return [
            [
                'name' => 'Construction',
                'icon' => 'construction',
                'path' => 'field',
                'roles' => [
                    ['name' => 'General Worker', 'abilities' => ['Site Cleaning', 'Concreting', 'Loading & Unloading', 'Formwork'], 'certificates' => ['Safety Orientation Course', 'First Aid']],
                    ['name' => 'Mason', 'abilities' => ['Brickwork', 'Plastering', 'Concreting', 'Reading Drawings'], 'certificates' => ['Safety Orientation Course', 'Trade Test Certificate']],
                    ['name' => 'Bar Bender', 'abilities' => ['Rebar Tying', 'Steel Fixing', 'Reading Drawings', 'Concreting'], 'certificates' => ['Safety Orientation Course', 'Trade Test Certificate']],
                    ['name' => 'Carpenter', 'abilities' => ['Formwork', 'Shuttering', 'Timber Work', 'Reading Drawings'], 'certificates' => ['Trade Test Certificate', 'Safety Orientation Course']],
                    ['name' => 'Plumber', 'abilities' => ['Pipe Fitting', 'Sanitary Installation', 'Leak Repairs', 'Water Tank Fitting'], 'certificates' => ['Trade Test Certificate', 'Plumbing Licence']],
                    ['name' => 'Electrician', 'abilities' => ['Wiring', 'Conduit Work', 'Switchboard Installation', 'Fault Finding'], 'certificates' => ['Electrical Wireman Licence', 'Trade Test Certificate', 'Safety Orientation Course']],
                    ['name' => 'Painter', 'abilities' => ['Surface Preparation', 'Spray Painting', 'Putty Work', 'Working at Heights'], 'certificates' => ['Work at Height Certificate', 'Safety Orientation Course']],
                    ['name' => 'Welder', 'abilities' => ['Arc Welding', 'Gas Welding', 'MIG Welding', 'Metal Cutting'], 'certificates' => ['Welding Trade Test', 'Hot Work Permit', 'Safety Orientation Course']],
                    ['name' => 'Scaffolder', 'abilities' => ['Scaffolding', 'Working at Heights', 'Load Calculation', 'Site Safety'], 'certificates' => ['Scaffolding Certificate', 'Work at Height Certificate']],
                    ['name' => 'Tiler', 'abilities' => ['Tiling', 'Waterproofing', 'Surface Preparation', 'Grouting'], 'certificates' => ['Trade Test Certificate']],
                    ['name' => 'Crane Operator', 'abilities' => ['Crane Operating', 'Load Calculation', 'Signalling', 'Site Safety'], 'certificates' => ['Crane Operator Licence', 'Safety Orientation Course']],
                    ['name' => 'Excavator Operator', 'abilities' => ['Excavator Operating', 'Earthworks', 'Site Safety'], 'certificates' => ['Heavy Machinery Licence', 'Safety Orientation Course']],
                    ['name' => 'Site Supervisor', 'abilities' => ['Reading Drawings', 'Team Supervision', 'Site Safety', 'Progress Reporting'], 'certificates' => ['Safety Orientation Course', 'Supervisor Safety Course', 'First Aid']],
                    ['name' => 'Safety Officer', 'abilities' => ['Site Safety', 'Risk Assessment', 'Toolbox Briefing', 'Incident Reporting'], 'certificates' => ['Safety Officer Certificate', 'First Aid', 'Confined Space Certificate']],
                ],
            ],
            [
                'name' => 'IT & Software',
                'icon' => 'code',
                'path' => 'professional',
                'roles' => [
                    ['name' => 'Software Engineer', 'abilities' => ['Python', 'Java', 'SQL', 'REST APIs', 'Git'], 'certificates' => []],
                    ['name' => 'Mobile Developer', 'abilities' => ['Flutter', 'Dart', 'Kotlin', 'REST APIs', 'Git'], 'certificates' => []],
                    ['name' => 'Web Developer', 'abilities' => ['JavaScript', 'TypeScript', 'React', 'Node.js', 'Git'], 'certificates' => []],
                    ['name' => 'Data Analyst', 'abilities' => ['SQL', 'Python', 'Data Analysis', 'Excel Modelling'], 'certificates' => []],
                    ['name' => 'IT Support', 'abilities' => ['Hardware Support', 'Network Troubleshooting', 'Ticketing Systems'], 'certificates' => []],
                    ['name' => 'QA Engineer', 'abilities' => ['Test Automation', 'Manual Testing', 'SQL', 'Git'], 'certificates' => []],
                    ['name' => 'DevOps Engineer', 'abilities' => ['Docker', 'AWS', 'CI/CD', 'Linux', 'Git'], 'certificates' => []],
                    ['name' => 'UI/UX Designer', 'abilities' => ['Figma', 'Wireframing', 'Prototyping', 'Design Systems'], 'certificates' => []],
                ],
            ],
            [
                'name' => 'Manufacturing',
                'icon' => 'precision_manufacturing_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Production Operator', 'abilities' => ['Machine Operating', 'Assembly Line Work', 'Shift Work', 'Reading Work Orders'], 'certificates' => ['Safety Training']],
                    ['name' => 'Machine Operator', 'abilities' => ['Machine Operating', 'Basic Maintenance', 'Quality Inspection', 'Shift Work'], 'certificates' => ['ITI Certificate', 'Safety Training']],
                    ['name' => 'Assembly Worker', 'abilities' => ['Assembly Line Work', 'Soldering', 'Quality Inspection', 'Standing Long Hours'], 'certificates' => ['Safety Training']],
                    ['name' => 'Quality Checker', 'abilities' => ['Quality Inspection', 'Measuring Instruments', 'Reading Work Orders', 'Defect Reporting'], 'certificates' => ['ITI Certificate']],
                    ['name' => 'Packer', 'abilities' => ['Packing', 'Labelling', 'Standing Long Hours', 'Stock Counting'], 'certificates' => []],
                    ['name' => 'CNC Operator', 'abilities' => ['CNC Machining', 'Reading Drawings', 'Measuring Instruments', 'Tool Setting'], 'certificates' => ['ITI Certificate', 'Trade Test Certificate']],
                    ['name' => 'Fitter', 'abilities' => ['Fitting', 'Basic Maintenance', 'Reading Drawings', 'Measuring Instruments'], 'certificates' => ['ITI Certificate', 'Trade Test Certificate']],
                    ['name' => 'Maintenance Technician', 'abilities' => ['Basic Maintenance', 'Fault Finding', 'Machine Operating', 'Electrical Repairs'], 'certificates' => ['ITI Certificate', 'Electrical Wireman Licence']],
                    ['name' => 'Line Leader', 'abilities' => ['Team Supervision', 'Assembly Line Work', 'Quality Inspection', 'Shift Work'], 'certificates' => ['Safety Training']],
                    ['name' => 'Store Keeper', 'abilities' => ['Stock Counting', 'Inventory Records', 'Reading Work Orders', 'Packing'], 'certificates' => []],
                ],
            ],
            [
                'name' => 'Warehouse & Logistics',
                'icon' => 'warehouse_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Warehouse Assistant', 'abilities' => ['Loading & Unloading', 'Stock Counting', 'Heavy Lifting', 'Scanning & Barcodes'], 'certificates' => ['Safety Training']],
                    ['name' => 'Picker & Packer', 'abilities' => ['Picking & Packing', 'Scanning & Barcodes', 'Standing Long Hours', 'Labelling'], 'certificates' => []],
                    ['name' => 'Forklift Driver', 'abilities' => ['Forklift Operating', 'Pallet Jack', 'Loading & Unloading', 'Stock Counting'], 'certificates' => ['Forklift Licence', 'Safety Training']],
                    ['name' => 'Reach Truck Operator', 'abilities' => ['Reach Truck Operating', 'Pallet Jack', 'Stock Counting'], 'certificates' => ['Reach Truck Licence', 'Forklift Licence']],
                    ['name' => 'Loader', 'abilities' => ['Loading & Unloading', 'Heavy Lifting', 'Pallet Jack'], 'certificates' => []],
                    ['name' => 'Storekeeper', 'abilities' => ['Inventory Records', 'Stock Counting', 'Scanning & Barcodes', 'Goods Receiving'], 'certificates' => []],
                    ['name' => 'Inventory Clerk', 'abilities' => ['Inventory Records', 'Stock Counting', 'Data Entry', 'Cycle Counting'], 'certificates' => []],
                    ['name' => 'Dispatch Assistant', 'abilities' => ['Goods Dispatch', 'Scanning & Barcodes', 'Route Planning', 'Documentation'], 'certificates' => []],
                    ['name' => 'Warehouse Supervisor', 'abilities' => ['Team Supervision', 'Inventory Records', 'Site Safety', 'Shift Work'], 'certificates' => ['Forklift Licence', 'Safety Training', 'First Aid']],
                ],
            ],
            [
                'name' => 'Healthcare & Nursing',
                'icon' => 'medical_services_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Staff Nurse', 'abilities' => ['Patient Care', 'Medication Administration', 'Wound Dressing', 'Ward Rounds'], 'certificates' => ['Nursing Registration', 'BLS / CPR']],
                    ['name' => 'Enrolled Nurse', 'abilities' => ['Patient Care', 'Vital Signs Monitoring', 'Wound Dressing', 'Medical Records'], 'certificates' => ['Nursing Registration', 'BLS / CPR']],
                    ['name' => 'Healthcare Assistant', 'abilities' => ['Patient Care', 'Mobility Assistance', 'Vital Signs Monitoring', 'Feeding Assistance'], 'certificates' => ['First Aid', 'Infection Control Training']],
                    ['name' => 'Patient Service Associate', 'abilities' => ['Medical Records', 'Appointment Scheduling', 'Customer Service'], 'certificates' => []],
                    ['name' => 'Therapy Assistant', 'abilities' => ['Mobility Assistance', 'Patient Care', 'Exercise Supervision'], 'certificates' => ['First Aid']],
                    ['name' => 'Lab Technician', 'abilities' => ['Sample Handling', 'Lab Testing', 'Medical Records', 'Infection Control'], 'certificates' => ['Lab Technician Diploma']],
                    ['name' => 'Pharmacy Assistant', 'abilities' => ['Dispensing Support', 'Stock Counting', 'Medical Records'], 'certificates' => []],
                    ['name' => 'Radiographer', 'abilities' => ['Imaging', 'Patient Care', 'Radiation Safety'], 'certificates' => ['Radiography Registration']],
                ],
            ],
            [
                'name' => 'Hospitality & F&B',
                'icon' => 'restaurant_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Kitchen Helper', 'abilities' => ['Food Preparation', 'Dishwashing', 'Kitchen Cleaning', 'Standing Long Hours'], 'certificates' => ['Food Handling Certificate', 'Basic Hygiene Course']],
                    ['name' => 'Cook', 'abilities' => ['Cooking', 'Food Preparation', 'Menu Execution', 'Stock Rotation'], 'certificates' => ['Food Handling Certificate', 'Food Hygiene Certificate']],
                    ['name' => 'Chef', 'abilities' => ['Cooking', 'Menu Planning', 'Kitchen Supervision', 'Cost Control'], 'certificates' => ['Food Hygiene Certificate', 'Culinary Certificate']],
                    ['name' => 'Waiter / Waitress', 'abilities' => ['Table Service', 'Order Taking', 'Customer Service', 'Cash Handling'], 'certificates' => ['Basic Hygiene Course']],
                    ['name' => 'Bartender', 'abilities' => ['Drink Preparation', 'Customer Service', 'Cash Handling', 'Stock Rotation'], 'certificates' => ['Basic Hygiene Course']],
                    ['name' => 'Barista', 'abilities' => ['Coffee Making', 'Customer Service', 'Cash Handling', 'Machine Cleaning'], 'certificates' => ['Barista Certificate', 'Basic Hygiene Course']],
                    ['name' => 'Dishwasher', 'abilities' => ['Dishwashing', 'Kitchen Cleaning', 'Standing Long Hours'], 'certificates' => ['Basic Hygiene Course']],
                    ['name' => 'Housekeeping Attendant', 'abilities' => ['Room Cleaning', 'Bed Making', 'Linen Handling', 'Guest Service'], 'certificates' => ['Basic Hygiene Course']],
                    ['name' => 'Hotel Receptionist', 'abilities' => ['Guest Service', 'Check-in Systems', 'Cash Handling', 'Customer Service'], 'certificates' => []],
                    ['name' => 'Banquet Staff', 'abilities' => ['Table Service', 'Event Setup', 'Customer Service'], 'certificates' => ['Basic Hygiene Course']],
                ],
            ],
            [
                'name' => 'Driving & Delivery',
                'icon' => 'local_shipping_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Delivery Rider', 'abilities' => ['Two Wheeler', 'Navigation Apps', 'Cash on Delivery', 'Customer Service'], 'certificates' => ['Two Wheeler Licence']],
                    ['name' => 'Van Driver', 'abilities' => ['Light Vehicle', 'Route Planning', 'Loading & Unloading', 'Navigation Apps'], 'certificates' => ['Class 3 Licence', 'Light Vehicle Licence']],
                    ['name' => 'Lorry Driver', 'abilities' => ['Heavy Vehicle', 'Route Planning', 'Loading & Unloading', 'Vehicle Maintenance'], 'certificates' => ['Class 4 Licence', 'Heavy Vehicle Licence']],
                    ['name' => 'Truck Driver', 'abilities' => ['Heavy Vehicle', 'Long Distance Driving', 'Night Driving', 'Vehicle Maintenance'], 'certificates' => ['Class 4 Licence', 'Class 5 Licence']],
                    ['name' => 'Bus Driver', 'abilities' => ['Heavy Vehicle', 'Passenger Safety', 'Route Planning'], 'certificates' => ['Class 4 Licence', 'Vocational Driving Licence']],
                    ['name' => 'Taxi / Private Hire Driver', 'abilities' => ['Light Vehicle', 'Navigation Apps', 'Customer Service', 'Night Driving'], 'certificates' => ['Vocational Driving Licence', 'Class 3 Licence']],
                    ['name' => 'Company Driver', 'abilities' => ['Light Vehicle', 'Route Planning', 'Vehicle Maintenance', 'Customer Service'], 'certificates' => ['Class 3 Licence']],
                    ['name' => 'Courier', 'abilities' => ['Two Wheeler', 'Navigation Apps', 'Documentation', 'Cash on Delivery'], 'certificates' => ['Two Wheeler Licence']],
                ],
            ],
            [
                'name' => 'Retail & Sales',
                'icon' => 'storefront_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Retail Assistant', 'abilities' => ['Customer Service', 'Stock Replenishment', 'Cash Handling', 'Standing Long Hours'], 'certificates' => []],
                    ['name' => 'Cashier', 'abilities' => ['Cash Handling', 'POS Systems', 'Customer Service', 'Billing'], 'certificates' => ['Cashier Training']],
                    ['name' => 'Sales Assistant', 'abilities' => ['Customer Service', 'Upselling', 'Product Knowledge', 'Cash Handling'], 'certificates' => []],
                    ['name' => 'Store Supervisor', 'abilities' => ['Team Supervision', 'Inventory Counting', 'Cash Handling', 'Rostering'], 'certificates' => ['Retail Service Certificate']],
                    ['name' => 'Merchandiser', 'abilities' => ['Visual Merchandising', 'Stock Replenishment', 'Inventory Counting'], 'certificates' => []],
                    ['name' => 'Stock Assistant', 'abilities' => ['Stock Replenishment', 'Inventory Counting', 'Heavy Lifting'], 'certificates' => []],
                    ['name' => 'Promoter', 'abilities' => ['Customer Service', 'Upselling', 'Product Knowledge'], 'certificates' => []],
                ],
            ],
            [
                'name' => 'Maid & Caregiver',
                'icon' => 'volunteer_activism_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Domestic Helper', 'abilities' => ['House Cleaning', 'Cooking', 'Laundry & Ironing', 'Marketing & Groceries'], 'certificates' => ['Domestic Helper Training', 'First Aid']],
                    ['name' => 'Live-in Maid', 'abilities' => ['House Cleaning', 'Cooking', 'Laundry & Ironing', 'Childcare'], 'certificates' => ['Domestic Helper Training', 'First Aid']],
                    ['name' => 'Part-time Cleaner', 'abilities' => ['House Cleaning', 'Laundry & Ironing', 'Toilet Cleaning'], 'certificates' => []],
                    ['name' => 'Nanny', 'abilities' => ['Childcare', 'Cooking', 'School Runs', 'Play & Learning'], 'certificates' => ['Infant Care Course', 'First Aid']],
                    ['name' => 'Elderly Caregiver', 'abilities' => ['Elderly Care', 'Medication Reminders', 'Feeding Assistance', 'Mobility Assistance'], 'certificates' => ['Caregiver Training', 'Elderly Care Course', 'First Aid']],
                    ['name' => 'Confinement Nanny', 'abilities' => ['Infant Care', 'Confinement Cooking', 'Postnatal Support'], 'certificates' => ['Confinement Nanny Course', 'Infant Care Course']],
                    ['name' => 'Cook / Helper', 'abilities' => ['Cooking', 'Marketing & Groceries', 'House Cleaning'], 'certificates' => ['Food Handling Certificate']],
                    ['name' => 'Home Nurse Aide', 'abilities' => ['Bedridden Care', 'Medication Reminders', 'Wound Dressing', 'Mobility Assistance'], 'certificates' => ['Caregiver Training', 'First Aid', 'BLS / CPR']],
                ],
            ],
            [
                'name' => 'Office & Administration',
                'icon' => 'business_center_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Admin Assistant', 'abilities' => ['MS Excel', 'MS Word', 'Filing & Records', 'Scheduling'], 'certificates' => []],
                    ['name' => 'Receptionist', 'abilities' => ['Customer Support', 'Scheduling', 'Email Handling', 'Call Handling'], 'certificates' => []],
                    ['name' => 'Data Entry Clerk', 'abilities' => ['Data Entry', 'MS Excel', 'Filing & Records'], 'certificates' => []],
                    ['name' => 'Operations Executive', 'abilities' => ['MS Excel', 'Scheduling', 'Vendor Coordination', 'Reporting'], 'certificates' => []],
                    ['name' => 'HR Executive', 'abilities' => ['Payroll Support', 'Recruitment Coordination', 'MS Excel', 'Filing & Records'], 'certificates' => []],
                    ['name' => 'Accounts Assistant', 'abilities' => ['Invoicing', 'Bookkeeping', 'MS Excel', 'Bank Reconciliation'], 'certificates' => []],
                    ['name' => 'Customer Service Officer', 'abilities' => ['Customer Support', 'Email Handling', 'Call Handling', 'CRM Systems'], 'certificates' => []],
                    ['name' => 'Office Manager', 'abilities' => ['Scheduling', 'Vendor Coordination', 'Reporting', 'Team Supervision'], 'certificates' => []],
                ],
            ],
            [
                'name' => 'Engineering',
                'icon' => 'engineering_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Civil Engineer', 'abilities' => ['Civil Engineering', 'AutoCAD', 'Site Supervision', 'Project Planning'], 'certificates' => ['Professional Engineer']],
                    ['name' => 'Mechanical Engineer', 'abilities' => ['Mechanical Design', 'AutoCAD', 'Preventive Maintenance', 'Project Planning'], 'certificates' => ['Professional Engineer']],
                    ['name' => 'Electrical Engineer', 'abilities' => ['Electrical Systems', 'AutoCAD', 'Fault Finding', 'Project Planning'], 'certificates' => ['Professional Engineer', 'Electrical Wireman Licence']],
                    ['name' => 'Site Engineer', 'abilities' => ['Site Supervision', 'Reading Drawings', 'Quality Control', 'Method Statements'], 'certificates' => ['Safety Officer Certificate']],
                    ['name' => 'Project Engineer', 'abilities' => ['Project Planning', 'Quality Control', 'Method Statements', 'Reporting'], 'certificates' => []],
                    ['name' => 'QA/QC Engineer', 'abilities' => ['Quality Control', 'Method Statements', 'Site Inspection', 'Reporting'], 'certificates' => []],
                    ['name' => 'Maintenance Engineer', 'abilities' => ['Preventive Maintenance', 'Fault Finding', 'HVAC', 'Electrical Systems'], 'certificates' => []],
                    ['name' => 'Draughtsman', 'abilities' => ['AutoCAD', 'Reading Drawings', 'BIM', 'Structural Design'], 'certificates' => []],
                ],
            ],
            [
                'name' => 'Security',
                'icon' => 'shield_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Security Guard', 'abilities' => ['Patrolling', 'Access Control', 'Incident Reporting', 'Night Shift'], 'certificates' => ['Security Licence', 'Basic Licensing Unit']],
                    ['name' => 'Security Officer', 'abilities' => ['Patrolling', 'Access Control', 'CCTV Monitoring', 'Report Writing'], 'certificates' => ['Security Licence', 'Fire Safety Certificate']],
                    ['name' => 'Senior Security Officer', 'abilities' => ['Team Supervision', 'Incident Reporting', 'Crowd Control', 'Fire Safety'], 'certificates' => ['Security Licence', 'Fire Safety Certificate', 'First Aid']],
                    ['name' => 'Site Security Supervisor', 'abilities' => ['Team Supervision', 'Access Control', 'Report Writing', 'Crowd Control'], 'certificates' => ['Security Licence', 'Supervisor Safety Course']],
                    ['name' => 'CCTV Operator', 'abilities' => ['CCTV Monitoring', 'Incident Reporting', 'Night Shift', 'Report Writing'], 'certificates' => ['Security Licence']],
                ],
            ],
            [
                'name' => 'Cleaning & Facilities',
                'icon' => 'cleaning_services_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Cleaner', 'abilities' => ['General Cleaning', 'Toilet Cleaning', 'Waste Disposal', 'Floor Polishing'], 'certificates' => ['Cleaning Certificate']],
                    ['name' => 'Office Cleaner', 'abilities' => ['General Cleaning', 'Waste Disposal', 'Pantry Upkeep'], 'certificates' => ['Cleaning Certificate']],
                    ['name' => 'School Cleaner', 'abilities' => ['General Cleaning', 'Toilet Cleaning', 'Waste Disposal'], 'certificates' => ['Cleaning Certificate']],
                    ['name' => 'Hospital Cleaner', 'abilities' => ['General Cleaning', 'Infection Control Cleaning', 'Waste Disposal'], 'certificates' => ['Cleaning Certificate', 'Chemical Handling']],
                    ['name' => 'Landscaper / Gardener', 'abilities' => ['Gardening', 'Grass Cutting', 'Plant Care', 'Irrigation'], 'certificates' => []],
                    ['name' => 'Pest Control Technician', 'abilities' => ['Pest Spraying', 'Chemical Handling', 'Site Inspection'], 'certificates' => ['Pest Control Licence', 'Chemical Handling']],
                    ['name' => 'Handyman', 'abilities' => ['Minor Repairs', 'Plumbing Repairs', 'Electrical Repairs', 'Painting'], 'certificates' => ['Trade Test Certificate']],
                    ['name' => 'Air-Con Technician', 'abilities' => ['Air-Con Servicing', 'Gas Charging', 'Fault Finding', 'Working at Heights'], 'certificates' => ['Air-Con Servicing Certificate', 'Work at Height Certificate']],
                    ['name' => 'Facilities Technician', 'abilities' => ['Minor Repairs', 'Fault Finding', 'Preventive Maintenance'], 'certificates' => ['Trade Test Certificate']],
                ],
            ],
            [
                'name' => 'Finance & Banking',
                'icon' => 'account_balance_outlined',
                'path' => 'field',
                'roles' => [
                    ['name' => 'Accounts Executive', 'abilities' => ['Bookkeeping', 'Accounts Payable', 'Accounts Receivable', 'Tally'], 'certificates' => []],
                    ['name' => 'Accountant', 'abilities' => ['Bookkeeping', 'Financial Analysis', 'GST Compliance', 'Bank Reconciliation'], 'certificates' => ['CA / CPA', 'Diploma in Accounting']],
                    ['name' => 'Financial Analyst', 'abilities' => ['Financial Analysis', 'Excel Modelling', 'Reporting', 'SAP'], 'certificates' => []],
                    ['name' => 'Audit Assistant', 'abilities' => ['Auditing', 'Bookkeeping', 'Documentation'], 'certificates' => ['Diploma in Accounting']],
                    ['name' => 'Bank Teller', 'abilities' => ['Cash Handling', 'Customer Service', 'Banking Systems'], 'certificates' => []],
                    ['name' => 'Credit Officer', 'abilities' => ['Credit Assessment', 'Documentation', 'Customer Service'], 'certificates' => []],
                    ['name' => 'Payroll Executive', 'abilities' => ['Payroll Support', 'MS Excel', 'Statutory Filing'], 'certificates' => []],
                ],
            ],
        ];
    }

    /** @return list<string> */
    public function languages(): array
    {
        return ['English', 'Tamil', 'Hindi', 'Malay', 'Mandarin', 'Bengali', 'Telugu', 'Malayalam', 'Kannada', 'Marathi', 'Punjabi', 'Nepali', 'Burmese', 'Tagalog', 'Indonesian', 'Thai', 'Hokkien', 'Cantonese'];
    }

    /** @return list<string> */
    public function workPermits(): array
    {
        return ['Citizen', 'Permanent Resident', 'Have a valid work permit', 'Have an employment pass', 'Need employer to sponsor a permit', 'Student pass', 'Not sure'];
    }

    /** @return list<string> */
    public function categoryNames(): array
    {
        return array_column($this->categories(), 'name');
    }

    /** @return array{name:string, icon:string, path:string, roles:array}|null */
    public function category(?string $name): ?array
    {
        foreach ($this->categories() as $category) {
            if ($category['name'] === $name) {
                return $category;
            }
        }

        return null;
    }

    /**
     * The jobs people actually do in a category.
     *
     * @return list<string>
     */
    public function roleNames(?string $category): array
    {
        return array_column($this->category($category)['roles'] ?? [], 'name');
    }

    /**
     * What someone in this role can do, in the words of the trade.
     *
     * The chosen role's own vocabulary leads, then the rest of the category —
     * so a plumber is offered pipe fitting and leak repairs before every task
     * anyone on a building site performs, without being boxed in to only those.
     *
     * @return list<string>
     */
    public function abilitiesFor(?string $category, ?string $role = null): array
    {
        $found = $this->category($category);

        if ($found === null) {
            return [];
        }

        $own = [];
        $rest = [];

        foreach ($found['roles'] as $entry) {
            if ($role !== null && $entry['name'] === $role) {
                $own = $entry['abilities'];
            } else {
                $rest = array_merge($rest, $entry['abilities']);
            }
        }

        // Order-preserving dedup: the first entries are what a candidate sees
        // before scrolling, so the commonest work stays at the top.
        return array_values(array_unique(array_merge($own, $rest)));
    }

    /** @return list<string> */
    public function certificatesFor(?string $category, ?string $role = null): array
    {
        $found = $this->category($category);

        if ($found === null) {
            return [];
        }

        foreach ($found['roles'] as $entry) {
            if ($role !== null && $entry['name'] === $role) {
                return $entry['certificates'];
            }
        }

        $all = [];
        foreach ($found['roles'] as $entry) {
            $all = array_merge($all, $entry['certificates']);
        }

        return array_values(array_unique($all));
    }
}
