<?php
declare(strict_types=1);

use Core\Database;
use Core\Token;
use Serv\UserServices;

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Support/helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "E-Services";
$eservices = [];
$databaseError = null;

try {
    // Use the database connection configured in .env
    $pdo = Database::connect();

    $stmt = $pdo->prepare("
        SELECT
            e.id,
            e.name,
            e.description,
            e.url,
            e.featured_image,
            e.status,
            e.active,
            e.author_id,
            e.editor_id,
            e.created_at,
            e.updated_at,
            NULL AS author_username,
            NULL AS editor_username
        FROM eservices AS e
        WHERE e.active = 1
        ORDER BY e.created_at DESC, e.id DESC
    ");

    $stmt->execute();
    $eservices = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    logError($e, 'Unable to load E-Services.');
    $databaseError = 'Unable to load E-Services. Check the database connection and server logs.';
}

$csrfToken = Token::generate();

$eservicesJson = json_encode(
    $eservices,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_INVALID_UTF8_SUBSTITUTE
);

if ($eservicesJson === false) {
    $eservicesJson = '[]';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UCC Admin | <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="icon" type="image/png" href="assets/images/ucc-LOGO.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Libre+Baskerville:wght@700&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/eservices.css">
</head>

<body data-page-title="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">

    <div class="dashboard-layout">

        <?php include __DIR__ . '/template/sidebar.php'; ?>

        <main class="main-content">

            <?php include __DIR__ . '/template/header.php'; ?>

            <section class="eservices-page">

                <div class="eservices-intro">
                    <div>
                        <p>
                            Manage the online services and external resources
                            available to the UCC community.
                        </p>
                    </div>
                </div>

                <?php if ($databaseError !== null): ?>
                    <div
                        class="eservices-database-error"
                        role="alert"
                        style="
                            padding: 14px 18px;
                            margin-bottom: 20px;
                            background: #fff1f2;
                            border: 1px solid #fecdd3;
                            border-radius: 10px;
                            color: #9f1239;
                        "
                    >
                        <?= htmlspecialchars($databaseError, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- TOOLBAR -->

                <div class="eservices-toolbar">

                    <div class="eservices-tools-left">

                        <div class="eservices-search">
                            <input
                                type="search"
                                id="search"
                                placeholder="Search e-services..."
                                aria-label="Search e-services"
                            >
                            <i data-lucide="search"></i>
                        </div>

                        <select
                            id="filter"
                            class="eservices-filter"
                            aria-label="Filter e-services"
                        >
                            <option value="all">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>

                        <select
                            id="sort"
                            class="eservices-filter"
                            aria-label="Order e-services"
                        >
                            <option value="created-desc">Newest created</option>
                            <option value="created-asc">Oldest created</option>
                            <option value="name-desc">Name: Z–A</option>
                            <option value="name-asc">Name: A–Z</option>
                        </select>

                    </div>

                    <button
                        type="button"
                        id="addBtn"
                        class="eservice-btn eservice-btn-primary"
                    >
                        <i data-lucide="plus"></i>
                        Add E-Service
                    </button>

                </div>

                <!-- E-SERVICES TABLE -->

                <div class="eservices-card">

                    <div class="eservices-card-head">

                        <h2>E-Services</h2>

                        <span id="itemCount" class="eservices-count">
                            <?= count($eservices) ?>
                            <?= count($eservices) === 1 ? 'service' : 'services' ?>
                        </span>

                    </div>

                    <div class="eservices-table-wrap">

                        <table class="eservices-table">

                            <thead>
                                <tr>
                                    <th>Logo</th>
                                    <th>Service Name</th>
                                    <th>Link</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody id="tableBody">
                                <tr>
                                    <td colspan="5" class="empty-row">
                                        Loading E-Services...
                                    </td>
                                </tr>
                            </tbody>

                        </table>

                    </div>

                </div>

                <!-- PAGINATION -->

                <nav
                    class="eservices-pagination"
                    id="eservicesPagination"
                    aria-label="E-Services pagination"
                    hidden
                >

                    <button
                        class="page-btn"
                        id="eservicesPrevPage"
                        type="button"
                        aria-label="Previous page"
                    >
                        <i data-lucide="chevron-left"></i>
                    </button>

                    <div
                        class="page-numbers"
                        id="eservicesPageNumbers"
                    ></div>

                    <button
                        class="page-btn"
                        id="eservicesNextPage"
                        type="button"
                        aria-label="Next page"
                    >
                        <i data-lucide="chevron-right"></i>
                    </button>

                    <span
                        class="page-total"
                        id="eservicesPageTotal"
                    >
                        1 page
                    </span>

                </nav>

                <!-- ADD / EDIT MODAL -->

                <div
                    id="mainModal"
                    class="eservice-modal"
                    aria-hidden="true"
                >

                    <div
                        class="eservice-modal-backdrop"
                        data-close-modal
                    ></div>

                    <div
                        class="eservice-modal-panel"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="formTitle"
                    >

                        <div class="eservice-modal-head">

                            <div>
                                <h2 id="formTitle">Add E-Service</h2>
                                <p>Enter the information for this e-service.</p>
                            </div>

                            <button
                                type="button"
                                id="exitBtn"
                                class="eservice-modal-close"
                                data-close-modal
                                aria-label="Close service form"
                            >
                                <i data-lucide="x"></i>
                            </button>

                        </div>

                        <form
                            id="form"
                            class="eservice-form"
                            method="post"
                            enctype="multipart/form-data"
                        >

                            <!-- CSRF TOKEN -->

                            <input
                                type="hidden"
                                name="<?= htmlspecialchars(Token::KEY, ENT_QUOTES, 'UTF-8') ?>"
                                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <!-- IMAGE UPLOAD -->

                            <div class="eservice-upload-area">

                                <div
                                    id="uploadImagePlaceholder"
                                    class="eservice-upload-placeholder"
                                >
                                    <i data-lucide="globe"></i>
                                    <strong>Service Logo</strong>
                                </div>

                                <img
                                    id="imagePreview"
                                    class="eservice-image-preview"
                                    alt="Service logo preview"
                                    hidden
                                >

                                <input
                                    type="file"
                                    id="image"
                                    name="featured_image"
                                    accept="image/png,image/jpeg,image/webp"
                                    hidden
                                >

                                <button
                                    type="button"
                                    id="uploadImageBtn"
                                    class="eservice-btn eservice-btn-ghost"
                                >
                                    <i data-lucide="upload"></i>
                                    Choose Photo
                                </button>

                                <button
                                    type="button"
                                    id="removeImageBtn"
                                    class="eservice-btn eservice-btn-danger"
                                    hidden
                                >
                                    <i data-lucide="x"></i>
                                    Remove Photo
                                </button>

                            </div>

                            <!-- FORM FIELDS -->

                            <div class="eservice-form-grid">

                                <label class="eservice-form-field full-width">

                                    <span>Service Name</span>

                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        placeholder="Enter service name"
                                        maxlength="150"
                                        required
                                    >

                                </label>

                                <label class="eservice-form-field full-width">

                                    <span>Status</span>

                                    <select
                                        id="status"
                                        name="status"
                                        required
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>

                                </label>

                                <label class="eservice-form-field full-width">

                                    <span>Description</span>

                                    <textarea
                                        id="description"
                                        name="description"
                                        rows="4"
                                        maxlength="500"
                                        placeholder="Enter service description"
                                        required
                                    ></textarea>

                                </label>

                                <label class="eservice-form-field full-width">

                                    <span>Website URL</span>

                                    <input
                                        type="url"
                                        id="url"
                                        name="url"
                                        placeholder="https://example.com"
                                        maxlength="2048"
                                    >

                                </label>

                            </div>

                            <!-- FORM ACTIONS -->

                            <div class="eservice-form-actions">

                                <button
                                    type="button"
                                    id="cancelBtn"
                                    class="eservice-btn eservice-btn-ghost"
                                    data-close-modal
                                >
                                    Cancel
                                </button>

                                <div class="eservice-form-actions-right">

                                    <button
                                        type="button"
                                        id="formPreviewBtn"
                                        class="eservice-btn eservice-btn-ghost"
                                    >
                                        <i data-lucide="eye"></i>
                                        Preview
                                    </button>

                                    <button
                                        type="submit"
                                        id="saveBtn"
                                        class="eservice-btn eservice-btn-primary"
                                    >
                                        <i data-lucide="save"></i>
                                        Save Service
                                    </button>

                                </div>

                            </div>

                        </form>

                    </div>

                </div>

                <!-- PREVIEW MODAL -->

                <div
                    id="previewModal"
                    class="eservice-modal"
                    aria-hidden="true"
                >

                    <div
                        class="eservice-modal-backdrop"
                        data-close-preview
                    ></div>

                    <article
                        class="eservice-modal-panel eservice-preview-panel"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="previewHeading"
                    >

                        <div class="eservice-modal-head">

                            <div>
                                <h2 id="previewHeading">
                                    E-Service Preview
                                </h2>
                            </div>

                            <button
                                type="button"
                                id="exitPreviewBtn"
                                class="eservice-modal-close"
                                data-close-preview
                                aria-label="Close service preview"
                            >
                                <i data-lucide="x"></i>
                            </button>

                        </div>

                        <div class="eservice-preview">

                            <div
                                id="previewImageWrap"
                                class="eservice-preview-image"
                            >

                                <div
                                    id="previewImageDefault"
                                    class="eservice-preview-default"
                                >
                                    <i data-lucide="globe"></i>
                                    <span>Service Logo</span>
                                </div>

                                <img
                                    id="previewImage"
                                    alt="Service logo"
                                    hidden
                                >

                            </div>

                            <div class="eservice-preview-body">

                                <h2 id="previewName">
                                    Service Name
                                </h2>

                                <p
                                    id="previewDescription"
                                    class="eservice-preview-text"
                                >
                                    Service description will appear here.
                                </p>

                                <p
                                    id="previewURL"
                                    class="eservice-preview-url"
                                ></p>

                            </div>

                        </div>

                        <footer class="eservice-preview-meta">

                            <div>
                                <span>CREATED BY</span>
                                <strong id="previewMetadataCreatedBy">—</strong>
                            </div>

                            <div>
                                <span>DATE CREATED</span>
                                <strong id="previewMetadataDateCreated">—</strong>
                            </div>

                            <div>
                                <span>LAST UPDATED BY</span>
                                <strong id="previewMetadataLastUpdatedBy">—</strong>
                            </div>

                        </footer>

                    </article>

                </div>

                <!-- TOAST -->

                <div
                    class="eservice-toast"
                    id="uiToast"
                    role="status"
                    aria-live="polite"
                ></div>

            </section>

        </main>

    </div>

    <!-- PASS DATABASE RECORDS TO JAVASCRIPT -->

    <script>
        window.UCC_ESERVICES = <?= $eservicesJson ?>;

        window.UCC_ESERVICES_DATABASE_ERROR =
            <?= json_encode(
                $databaseError,
                JSON_HEX_TAG |
                JSON_HEX_AMP |
                JSON_HEX_APOS |
                JSON_HEX_QUOT
            ) ?>;
    </script>

    <script src="assets/js/ucc-common.js"></script>
    <script src="assets/js/eservices.js"></script>

    <script>
        if (window.lucide) {
            window.lucide.createIcons();
        }
    </script>

</body>
</html>