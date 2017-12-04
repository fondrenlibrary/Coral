<?php
$titleId = filter_input(INPUT_GET, 'titleId', FILTER_SANITIZE_STRING);

if(empty($titleId)){
    echo '<p>No title ID provided</p>';
    exit;
}

$ebscoKb = EbscoKbService::getInstance();
$title = $ebscoKb->getTitle($titleId);

?>
<?php include_once __DIR__.'/../css/ebscoKbCss.php'; ?>
<div id="div_ebscoKbTitleDetails" class="ebsco-layout">

    <div class="formTitle" style="width:715px; margin-bottom:5px;position:relative;"><span class="headerText"><?php echo _("EBSCO Kb Title Details");?></span></div>

    <div class="container">
        <div class="row">
            <div class="col-8"><h1 style="line-height: 1em;"><?php echo $title->titleName; ?></h1></div>
            <div class="col-4" style="text-align: right;">KbID: <?php echo $title->titleId; ?></div>
        </div>
        <div class="row" style="margin-top: 1em;">
            <div class="col-12">
                <p style="font-size: 1rem;"><?php echo $title->description; ?></p>
            </div>
        </div>
        <div class="row" style="margin-top: 1em;">
            <div class="col-12">
                <h2>Title details</h2>
                <div class="row">
                    <div class="col-6">
                        <dl>
                            <dt>Publication Type</dt>
                            <dd><?php echo $title->pubType; ?></dd>

                            <?php if(!empty($title->edition)): ?>
                                <dt>Edition</dt>
                                <dd><?php echo $title->edition; ?></dd>
                            <?php endif; ?>

                            <dt>Peer Reviewed</dt>
                            <dd><?php echo $title->isPeerReviewed; ?></dd>

                            <dt>Publisher</dt>
                            <dd><?php echo $title->publisherName; ?></dd>
                        </dl>
                    </div>
                    <div class="col-6">
                        <dl>
                            <dt>Subjects</dt>
                            <dd>
                                <ul>
                                    <?php foreach($title->subjects as $subject): ?>
                                        <li><?php echo $subject; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </dd>

                            <dt>ISNs</dt>
                            <dd>
                                <ul style="list-style: none; ">
                                    <?php
                                    foreach($title->identifiersList as $identifier){
                                        if(in_array($identifier['type'], [0,1,2])) {
                                            switch($identifier['subtype']){
                                                case 1:
                                                    $subtype = ' (Print)';
                                                    break;
                                                case 2:
                                                    $subtype = ' (Electronic)';
                                                    break;
                                                default:
                                                    $subtype = '';
                                            }
                                            echo sprintf('<li>%s%s</li>', $identifier['id'], $subtype);
                                        }
                                    }
                                    ?>
                                </ul>
                            </dd>

                            <?php if(!empty($title->contributorList)): ?>
                                <dt>Contributors</dt>
                                <dd><?php echo implode(', ', $title->contributorList); ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-12" style="margin-top: 1em;">
                <h2>Available in the following packages:</h2>
                <div class="row">
                    <div class="col-4">
                        <label for="showAllPackages">
                            <input type="checkbox" id="showAllPackages"> Show all packages
                        </label>
                    </div>
                </div>
                <div class="row">
                    <?php foreach($title->customerResourcesList as $resource): ?>
                    <div class="col-12 packageOption <?php echo $resource->isSelected ? 'selectedPackage' : ''; ?>">
                        <div class="card" style="margin-top: 1em;">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-8">
                                        <h3 style="padding-left: 5px;">
                                            <?php if($resource->isSelected): ?>
                                                <i class="fa fa-check-square-o fa-lg text-success" title="Selected in EBSCO Kb" style="margin-left: -15px;"></i>
                                            <?php else: ?>
                                                <i class="fa fa-ban fa-lg text-danger" title="Not selected in EBSCO Kb" style="margin-left: -15px;"></i>
                                            <?php endif; ?>
                                            <?php echo $resource->packageName; ?>
                                        </h3>
                                    </div>
                                    <div class="col-4" style="text-align: right">
                                        <button
                                                class="setPackage btn btn-primary"
                                                onclick="tb_remove();"
                                                data-vendor-id="<?php echo $resource->vendorId; ?>"
                                                data-package-id="<?php echo $resource->packageId; ?>"
                                                data-package-name="<?php echo $resource->packageName; ?>">
                                            <?php echo _("View Titles"); ?>
                                        </button>
                                        <button
                                                class="btn btn-primary"
                                                style="margin-left: 8px;">
                                            <?php echo _("Import Package"); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <p>Vendor: <?php echo $resource->vendorName; ?></p>
                                <dl>
                                    <dt>Coverage Statement</dt>
                                    <dd><?php echo $resource->coverageStatement; ?></dd>
                                    <dt>Embargo</dt>
                                    <dd><?php echo $resource->embargoStatement; ?></dd>
                                    <dt>Resource Url</dt>
                                    <dd><a href="<?php echo $resource->url; ?>"><?php echo $resource->url; ?></a></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row" style="margin-top: 1em;">
            <div class="col-12">
                <a
                    href="ajax_forms.php?action=getEbscoKbTitleImportForm&height=700&width=730&modal=true&titleId=<?php echo $title->titleId; ?>"
                    class="thickbox btn btn-primary">
                    <?php echo _('import'); ?>
                </a>
                <button onclick="tb_remove();" class="btn btn-primary ml-1"><?php echo _("cancel");?></button>
            </div>
        </div>
    </div>
</div>

