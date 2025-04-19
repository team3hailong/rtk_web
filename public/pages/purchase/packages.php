<?php
session_start();

// --- Base URL Configuration ---
// Assuming public is the web root, base URL path is /
$base_path = '/'; // Use this for links

// --- Project Root Path for Includes ---
$project_root_path = dirname(dirname(dirname(__DIR__)));

// --- Include Database and Config ---
require_once $project_root_path . '/private/config/config.php';
require_once $project_root_path . '/private/classes/Database.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/auth/login.php'); // Corrected path from root
    exit;
}

// --- User Info (Example) ---
$user_username = $_SESSION['username'] ?? 'Người dùng';

// ===============================================
// == LẤY DỮ LIỆU CÁC GÓI TỪ DATABASE ==
// ===============================================
$db = new Database();
$conn = $db->connect();
$all_packages = [];
try {
    $stmt = $conn->prepare("SELECT package_id, name, price, duration_text, features_json, is_recommended, button_text, savings_text FROM package WHERE is_active = 1 ORDER BY display_order ASC");
    $stmt->execute();
    $all_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching packages: " . $e->getMessage());
}
$db->close();

// --- Include Header ---
include $project_root_path . '/private/includes/header.php';
?>

<!-- CSS cho Trang Gói Tài Khoản -->
<style>
    /* --- Layout Wrapper (Giả sử đã có trong CSS chung) --- */
    /* .dashboard-wrapper { ... } */
    /* .content-wrapper { ... } */

    /* --- Grid Container cho các Gói --- */
    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    /* --- Styling cho Từng Card Gói --- */
    .package-card {
        background-color: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--rounded-lg);
        padding: 2rem;
        display: flex;
        flex-direction: column;
        text-align: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .package-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    /* Tiêu đề Gói */
    .package-card h3 {
        font-size: var(--font-size-lg);
        font-weight: var(--font-semibold);
        color: var(--gray-800);
        margin-bottom: 0.75rem;
    }

    /* Giá Gói */
    .package-price {
        font-size: 1.75rem;
        font-weight: var(--font-bold);
        color: var(--primary-600);
        margin-bottom: 0.5rem; /* Giảm margin dưới giá */
    }
    .package-price .duration {
        font-size: var(--font-size-sm);
        font-weight: var(--font-normal);
        color: var(--gray-500);
    }
    /* Text tiết kiệm (tùy chọn) */
    .package-savings {
        font-size: var(--font-size-xs);
        color: var(--primary-600);
        margin-bottom: 1.5rem; /* Đặt margin dưới text tiết kiệm */
        display: block; /* Để nó chiếm 1 dòng riêng */
        min-height: 1.2em; /* Giữ khoảng trống ngay cả khi không có text */
    }


    /* Danh sách Tính năng */
    .package-features {
        list-style: none;
        padding: 0;
        margin-bottom: 2rem;
        text-align: left;
    }

    .package-features li {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        color: var(--gray-700);
        font-size: var(--font-size-sm);
    }

    .package-features li i {
        width: 1.1em; /* Đảm bảo icon có không gian */
        text-align: center;
        /* Màu sắc được đặt trong vòng lặp PHP */
    }
     .package-features li i.fa-check { color: var(--primary-500); }
     .package-features li i.fa-times { color: var(--gray-400); }


    /* Nút Chọn Gói */
    .btn-select-package {
        display: inline-block;
        width: 100%;
        padding: 0.75rem 1.5rem;
        /* Change background to green */
        background-color: var(--green-500, #22c55e); /* Fallback green */
        color: white;
        border: none;
        border-radius: var(--rounded-md);
        font-weight: var(--font-semibold);
        text-decoration: none;
        transition: background-color 0.2s ease;
        cursor: pointer;
        margin-top: auto; /* Đảm bảo nút luôn ở dưới cùng */
        box-sizing: border-box; /* Prevent overflow due to padding */
    }

    .btn-select-package:hover {
         /* Darker green on hover */
        background-color: var(--green-600, #16a34a); /* Fallback darker green */
    }
    /* Nút "Liên hệ mua" có style khác */
    .btn-select-package.contact {
        /* Change background to black/dark gray */
        background-color: var(--gray-800, #1f2937); /* Fallback dark gray */
        color: white;
    }
    .btn-select-package.contact:hover {
         /* Slightly lighter black/dark gray on hover */
         background-color: var(--gray-900, #111827); /* Fallback darker gray */
    }

    /* --- Styling cho Gói Đề Xuất --- */
    .package-card.recommended {
        border-color: var(--primary-500);
        border-width: 2px;
        position: relative;
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.15);
    }
    .package-card.recommended:hover {
         box-shadow: 0 8px 25px rgba(34, 197, 94, 0.2);
    }

    .recommended-badge {
        position: absolute;
        top: -1px;
        left: 50%;
        transform: translateX(-50%) translateY(-50%);
        background-color: var(--primary-500);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: var(--rounded-full);
        font-size: var(--font-size-xs);
        font-weight: var(--font-semibold);
        z-index: 1;
    }

    /* --- Responsive --- */
    @media (max-width: 768px) {
        .packages-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
         .content-wrapper {
            padding: 1rem !important;
        }
        .package-card {
            padding: 1.5rem;
        }
        .package-price {
            font-size: 1.5rem;
        }
    }
</style>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php include $project_root_path . '/private/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content-wrapper">
        <h2 class="text-2xl font-semibold mb-4">Mua Gói Tài Khoản</h2>
        <p class="text-gray-600 mb-6">Chọn gói phù hợp với nhu cầu sử dụng của bạn.</p>

        <!-- Grid chứa các gói (Tạo bằng vòng lặp PHP) -->
        <div class="packages-grid">

            <?php if (empty($all_packages)): ?>
                <p class="text-center text-gray-500 col-span-full">Hiện tại không có gói dịch vụ nào.</p>
            <?php else: ?>
                <?php foreach ($all_packages as $package): ?>
                    <?php
                        // Decode features JSON
                        $features = json_decode($package['features_json'], true); // true for associative array
                        if ($features === null) {
                            $features = []; // Handle potential JSON decode error
                        }

                        // Xác định class cho card (thêm 'recommended' nếu cần)
                        $card_classes = 'package-card';
                        if ($package['is_recommended']) {
                            $card_classes .= ' recommended';
                        }
                        // Tạo URL cho trang chi tiết - Use base_path
                        $details_url = $base_path . '/pages/purchase/details.php?package=' . htmlspecialchars($package['package_id']);
                        // Xác định class cho nút bấm (thêm 'contact' nếu là nút liên hệ)
                        $button_classes = 'btn-select-package';
                        $is_contact_button = ($package['button_text'] === 'Liên hệ mua');
                        if ($is_contact_button) {
                             $button_classes .= ' contact';
                        }
                    ?>
                    <div class="<?php echo $card_classes; ?>">
                        <?php if ($package['is_recommended']): ?>
                            <div class="recommended-badge">Phổ biến</div>
                        <?php endif; ?>

                        <h3><?php echo htmlspecialchars($package['name']); ?></h3>

                        <div class="package-price">
                            <?php echo number_format($package['price'], 0, ',', '.'); ?>đ
                            <span class="duration"><?php echo htmlspecialchars($package['duration_text']); ?></span>
                        </div>

                        <!-- Hiển thị text tiết kiệm nếu có -->
                        <span class="package-savings">
                            <?php echo isset($package['savings_text']) ? htmlspecialchars($package['savings_text']) : '&nbsp;'; ?>
                        </span>

                        <ul class="package-features">
                            <?php foreach ($features as $feature): ?>
                                <li>
                                    <i class="fas <?php echo htmlspecialchars($feature['icon']); ?>" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($feature['text']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- Nút bấm với link chính xác -->
                        <a href="<?php echo $details_url; ?>" class="<?php echo $button_classes; ?>">
                            <?php echo htmlspecialchars($package['button_text']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div> <!-- /.packages-grid -->

    </main>
</div>

<!-- JavaScript (Nếu cần) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add JS logic here if needed
});
</script>

<?php
// --- Include Footer ---
include $project_root_path . '/private/includes/footer.php';
?>