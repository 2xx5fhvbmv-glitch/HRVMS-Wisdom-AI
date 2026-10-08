<?php

// Demo ENV organisation and staff template (spec §4–§5). 100 people.
// positions: [department, title, level, section, headcount, demo login (local part) or null, fixed nationality or null]
// Levels map to ranks in DemoOrganisationSeeder::LEVELS (the GM's EXCOM title becomes rank 8 in the importer).

return [
    'divisions' => [
        'Administration & General' => ['Executive Office', 'Human Resources', 'Finance', 'Engineering'],
        'Rooms' => ['Front Office', 'Housekeeping'],
        'Food & Beverage' => ['F&B Service', 'Kitchen'],
    ],

    'sections' => [
        'Housekeeping' => ['Rooms', 'Laundry', 'Public Area'],
        'F&B Service' => ['Main Restaurant', 'Bar'],
        'Kitchen' => ['Hot Kitchen', 'Pastry'],
    ],

    'positions' => [
        ['Executive Office', 'General Manager', 'EXCOM', '', 1, 'gm', 'British'],
        ['Executive Office', 'Resident Manager', 'EXCOM', '', 1, 'excom', null],
        ['Executive Office', 'Executive Assistant', 'SUP', '', 1, null, null],

        ['Human Resources', 'Director Of Human Resources', 'EXCOM', '', 1, 'hr', 'Maldivian'],
        ['Human Resources', 'Human Resources Manager', 'HOD', '', 1, 'hr.manager', null],
        ['Human Resources', 'Human Resources Officer', 'SUP', '', 1, null, null],
        ['Human Resources', 'Human Resources Assistant', 'LINE', '', 1, null, null],

        ['Finance', 'Director Of Finance', 'EXCOM', '', 1, 'finance', 'Indian'],
        ['Finance', 'Finance Manager', 'HOD', '', 1, 'finance.manager', null],
        ['Finance', 'Accountant', 'SUP', '', 1, null, null],
        ['Finance', 'Accounts Clerk', 'LINE', '', 2, null, null],
        ['Finance', 'Storekeeper', 'LINE', '', 1, null, null],

        ['Engineering', 'Chief Engineer', 'HOD', '', 1, 'hod.engineering', null],
        ['Engineering', 'Assistant Chief Engineer', 'MGR', '', 1, null, null],
        ['Engineering', 'Engineering Supervisor', 'SUP', '', 1, null, null],
        ['Engineering', 'Technician', 'LINE', '', 5, null, null],
        ['Engineering', 'Electrician', 'LINE', '', 3, null, null],

        ['Front Office', 'Front Office Manager', 'HOD', '', 1, 'hod.frontoffice', null],
        ['Front Office', 'Assistant Front Office Manager', 'MGR', '', 1, null, null],
        ['Front Office', 'Guest Service Supervisor', 'SUP', '', 2, null, null],
        ['Front Office', 'Guest Service Agent', 'LINE', '', 6, null, null],
        ['Front Office', 'Butler', 'LINE', '', 4, null, null],

        ['Housekeeping', 'Executive Housekeeper', 'HOD', '', 1, 'hod.housekeeping', null],
        ['Housekeeping', 'Assistant Housekeeper', 'MGR', '', 1, null, null],
        ['Housekeeping', 'Housekeeping Supervisor', 'SUP', 'Rooms', 2, 'supervisor', null],
        ['Housekeeping', 'Room Attendant', 'LINE', 'Rooms', 12, 'employee', null],
        ['Housekeeping', 'Laundry Attendant', 'LINE', 'Laundry', 4, null, null],
        ['Housekeeping', 'Gardener', 'LINE', 'Public Area', 4, null, null],

        ['F&B Service', 'Food And Beverage Manager', 'HOD', '', 1, 'hod.fnb', null],
        ['F&B Service', 'Restaurant Manager', 'MGR', 'Main Restaurant', 1, 'manager', null],
        ['F&B Service', 'Captain', 'SUP', 'Main Restaurant', 2, null, null],
        ['F&B Service', 'Waiter', 'LINE', 'Main Restaurant', 12, null, null],
        ['F&B Service', 'Bartender', 'LINE', 'Bar', 4, null, null],

        ['Kitchen', 'Executive Chef', 'HOD', '', 1, 'hod.kitchen', 'Italian'],
        ['Kitchen', 'Sous Chef', 'MGR', 'Hot Kitchen', 1, null, null],
        ['Kitchen', 'Chef De Partie', 'SUP', 'Hot Kitchen', 3, null, null],
        ['Kitchen', 'Commis Chef', 'LINE', 'Hot Kitchen', 8, null, null],
        ['Kitchen', 'Pastry Commis', 'LINE', 'Pastry', 2, null, null],
        ['Kitchen', 'Kitchen Steward', 'LINE', '', 3, null, null],
    ],

    // Expat mix (weights); the Maldivian share is drawn per reset (40–60 %).
    'expat_weights' => ['Indian' => 30, 'Sri Lankan' => 20, 'Bangladeshi' => 25, 'Nepalese' => 10, 'Filipino' => 10, 'British' => 3, 'German' => 2],

    // nationality => [share of Muslims, male first names, female first names, last names, passport prefix]
    'names' => [
        'Maldivian' => [1.0,
            ['Ahmed', 'Mohamed', 'Ibrahim', 'Hussain', 'Ali', 'Hassan', 'Abdulla', 'Ismail', 'Moosa', 'Yoosuf', 'Shifan', 'Aishan', 'Zayan', 'Rayyan', 'Ilham', 'Azim', 'Nashid'],
            ['Aishath', 'Fathimath', 'Mariyam', 'Hawwa', 'Khadeeja', 'Aminath', 'Shifa', 'Zeena', 'Nashwa', 'Raufa', 'Lamha', 'Shaha'],
            ['Rasheed', 'Naseem', 'Shareef', 'Manik', 'Didi', 'Hameed', 'Zahir', 'Waheed', 'Latheef', 'Adam', 'Saleem', 'Faiz', 'Shakir', 'Riza'], 'A'],
        'Indian' => [0.15,
            ['Rahul', 'Arjun', 'Vikram', 'Suresh', 'Anil', 'Rajesh', 'Deepak', 'Sanjay', 'Kiran', 'Manoj', 'Imran', 'Faisal', 'Joseph', 'Arun'],
            ['Priya', 'Anjali', 'Divya', 'Sneha', 'Kavya', 'Meera', 'Lakshmi', 'Ayesha'],
            ['Sharma', 'Nair', 'Menon', 'Pillai', 'Kumar', 'Reddy', 'Iyer', 'Das', 'Thomas', 'Fernandes', 'Khan', 'Varghese'], 'N'],
        'Sri Lankan' => [0.1,
            ['Nuwan', 'Kasun', 'Chamara', 'Dinesh', 'Ruwan', 'Lahiru', 'Pradeep', 'Tharindu', 'Rizwan'],
            ['Nadeesha', 'Dilini', 'Sanduni', 'Chathurika', 'Ishara'],
            ['Perera', 'Fernando', 'Silva', 'Jayasinghe', 'Wickramasinghe', 'Bandara', 'Rathnayake', 'Dissanayake'], 'N'],
        'Bangladeshi' => [0.95,
            ['Rahim', 'Karim', 'Shakil', 'Jahid', 'Rasel', 'Mamun', 'Sohel', 'Arif', 'Habib', 'Monir'],
            ['Nasrin', 'Farzana', 'Taslima', 'Sharmin'],
            ['Hossain', 'Rahman', 'Islam', 'Uddin', 'Ahmed', 'Miah', 'Chowdhury', 'Alam'], 'B'],
        'Nepalese' => [0.0,
            ['Bikash', 'Ramesh', 'Suman', 'Prakash', 'Dipendra', 'Sanjeev'],
            ['Sita', 'Anita', 'Sunita', 'Pooja'],
            ['Shrestha', 'Gurung', 'Tamang', 'Thapa', 'Rai', 'Magar'], 'P'],
        'Filipino' => [0.0,
            ['Mark', 'Jerome', 'Ramon', 'Paolo', 'Carlo'],
            ['Maria', 'Joy', 'Grace', 'Kristine', 'Angelica'],
            ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza'], 'P'],
        'British' => [0.0, ['James', 'Oliver', 'Thomas', 'William'], ['Emma', 'Sophie', 'Charlotte'], ['Smith', 'Taylor', 'Brown', 'Wilson', 'Clarke'], '5'],
        'German' => [0.0, ['Lukas', 'Felix', 'Jonas'], ['Anna', 'Lena', 'Laura'], ['Muller', 'Schmidt', 'Fischer', 'Weber'], 'C'],
        'Italian' => [0.0, ['Marco', 'Luca', 'Matteo'], ['Giulia', 'Chiara'], ['Rossi', 'Bianchi', 'Romano', 'Ferrari'], 'Y'],
    ],

    // Monthly basic salary in USD by level [min, max]; Maldivians are paid the MVR equivalent.
    'salary' => ['GM' => [11000, 13000], 'EXCOM' => [6000, 8500], 'HOD' => [3000, 4500], 'MGR' => [1800, 2600], 'SUP' => [900, 1400], 'LINE' => [550, 850]],

    // Maldives public holidays. Fixed dates repeat yearly; Islamic ones (approximate) per year.
    'holidays_fixed' => ['01-01' => "New Year's Day", '05-01' => 'Labour Day', '07-26' => 'Independence Day', '11-03' => 'Victory Day', '11-11' => 'Republic Day'],
    'holidays_islamic' => [
        2025 => ['2025-03-31' => 'Eid al-Fitr', '2025-06-06' => 'Eid al-Adha', '2025-06-26' => 'Islamic New Year', '2025-08-24' => 'National Day', '2025-09-04' => "Prophet's Birthday"],
        2026 => ['2026-03-20' => 'Eid al-Fitr', '2026-05-27' => 'Eid al-Adha', '2026-06-16' => 'Islamic New Year', '2026-08-13' => 'National Day', '2026-08-25' => "Prophet's Birthday"],
        2027 => ['2027-03-09' => 'Eid al-Fitr', '2027-05-16' => 'Eid al-Adha', '2027-06-06' => 'Islamic New Year', '2027-08-02' => 'National Day', '2027-08-14' => "Prophet's Birthday"],
    ],
];
