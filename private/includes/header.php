<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Đo đạc</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Your CSS files -->
    <!-- Base styles, variables etc. should be loaded here -->
    <link rel="stylesheet" href="/assets/css/base.css"> <!-- Assuming base styles are here -->
    <link rel="stylesheet" href="/assets/css/layouts/main-content.css"> <!-- Styles for content area -->
    <!-- Include other necessary CSS like components (buttons, cards, etc.) -->
    <link rel="stylesheet" href="/assets/css/components/cards.css">
    <link rel="stylesheet" href="/assets/css/components/tables.css">

    <!-- Add this style for dashboard specific layout -->
    <style>
        /* Global Font */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Keep Stats Cards styles if they are specific to header/dashboard page */
        /* Or move them to a dedicated dashboard.css */
        .stats-grid { /* Renamed from .stats for clarity */
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--rounded-lg); /* Use variable */
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); /* Softer shadow */
            border: 1px solid var(--gray-200); /* Add subtle border */
        }

        .stat-card .icon {
            font-size: 1.75rem; /* Slightly larger icon */
            margin-bottom: 1rem;
            display: block;
            width: 40px; /* Give icon a fixed size bg */
            height: 40px;
            line-height: 40px;
            text-align: center;
            border-radius: 50%;
            color: white; /* Icon color */
        }

        /* Specific background colors for icons */
        .stat-card .icon.success { background-color: var(--green-500, #22c55e); }
        .stat-card .icon.warning { background-color: var(--yellow-500, #f59e0b); }
        .stat-card .icon.info    { background-color: var(--blue-500, #3b82f6); }
        .stat-card .icon.primary { background-color: var(--primary-500, #10b981); }

        .stat-card h3 {
            color: var(--gray-500); /* Use variable */
            font-size: var(--font-size-sm); /* Use variable */
            font-weight: var(--font-medium);
            margin-bottom: 0.25rem; /* Smaller margin */
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .stat-card p.value { /* Added class 'value' for clarity */
            font-size: 1.75rem; /* Larger value text */
            font-weight: var(--font-semibold); /* Use variable */
            color: var(--gray-800); /* Use variable */
            margin: 0;
            line-height: 1.2;
        }

        /* Recent Activity - Keep if needed, or move to dashboard.css */
        .recent-activity {
            background: white;
            padding: 1.5rem;
            border-radius: var(--rounded-lg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid var(--gray-200);
        }

        .recent-activity h3 {
            color: var(--gray-800);
            font-size: var(--font-size-lg);
            font-weight: var(--font-semibold);
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 0.75rem;
        }
        .activity-list {
            max-height: 300px; /* Limit height and make scrollable */
            overflow-y: auto;
        }

        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px dashed var(--gray-200);
        }
        .activity-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .activity-item:first-child {
            padding-top: 0;
        }

        .activity-item p {
            color: var(--gray-700);
            margin-bottom: 0.25rem;
            font-size: var(--font-size-sm);
        }
        .activity-item small {
            color: var(--gray-500);
            font-size: var(--font-size-xs);
        }

        /* Responsive Layout Adjustments */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr; /* Stack cards on mobile */
            }
        }
    </style>
</head>
<body>
<!-- Body content starts here -->
</body>
</html>