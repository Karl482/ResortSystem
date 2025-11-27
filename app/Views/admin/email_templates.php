<?php
$pageTitle = "Manage Email Templates";
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container mt-4">
    <h1>Manage Email Templates</h1>
    <p class="text-muted">Customize the content of automated emails sent to users.</p>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message']; ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error_message']; ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="accordion" id="emailTemplatesAccordion">
        <?php foreach ($defaultTemplates as $type => $defaultContent): 
            $customTemplate = $customTemplates[$type] ?? null;
        ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-<?= $type; ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $type; ?>">
                        <?= ucwords(str_replace('_', ' ', $type)); ?>
                    </button>
                </h2>
                <div id="collapse-<?= $type; ?>" class="accordion-collapse collapse" data-bs-parent="#emailTemplatesAccordion">
                    <div class="accordion-body">
                        <form action="?controller=admin&action=updateEmailTemplate" method="POST">
                            <input type="hidden" name="templateId" value="<?= $customTemplate['TemplateID']; ?>">

                            <div class="mb-3">
                                <strong>Choose Template to Use:</strong>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="template_choice" id="choice-default-<?= $type; ?>" value="default" <?= !$customTemplate['UseCustom'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="choice-default-<?= $type; ?>">Use Default Template</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="template_choice" id="choice-custom-<?= $type; ?>" value="custom" <?= $customTemplate['UseCustom'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="choice-custom-<?= $type; ?>">Use Custom Template</label>
                                </div>
                            </div>

                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="default-tab-<?= $type; ?>" data-bs-toggle="tab" data-bs-target="#default-content-<?= $type; ?>" type="button">Default</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="custom-tab-<?= $type; ?>" data-bs-toggle="tab" data-bs-target="#custom-content-<?= $type; ?>" type="button">Custom</button>
                                </li>
                            </ul>

                            <div class="tab-content border border-top-0 p-3 mb-3">
                                <div class="tab-pane fade show active" id="default-content-<?= $type; ?>" role="tabpanel">
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Default Subject:</strong></label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($defaultContent['Subject']); ?>" disabled readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><strong>Default Body:</strong></label>
                                        <div class="p-2 border bg-light"><?= $defaultContent['Body']; ?></div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="custom-content-<?= $type; ?>" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="subject-<?= $customTemplate['TemplateID']; ?>" class="form-label"><strong>Custom Subject:</strong></label>
                                        <input type="text" class="form-control" id="subject-<?= $customTemplate['TemplateID']; ?>" name="subject" value="<?= htmlspecialchars($customTemplate['Subject']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="body-<?= $customTemplate['TemplateID']; ?>" class="form-label"><strong>Custom Body:</strong></label>
                                        <textarea class="form-control" id="body-<?= $customTemplate['TemplateID']; ?>" name="body" rows="10"><?= htmlspecialchars($customTemplate['Body']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-text mb-3">
                                Available placeholders: <code>{{customer_name}}</code>, <code>{{resort_name}}</code>, <code>{{booking_date}}</code>, <code>{{booking_id}}</code>, <code>{{timeslot}}</code>, <code>{{expiration_time}}</code>, <code>{{payment_reference}}</code>, <code>{{remaining_balance}}</code>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>