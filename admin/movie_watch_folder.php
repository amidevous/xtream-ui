<?php
include "session.php"; include "functions.php";
if (!$rPermissions["is_admin"]) { exit; }

if (isset($_POST["submit_folder"])) {
    $rPath = $_POST["selected_path"];
    if ((strlen($rPath) > 0) && ($rPath <> "/")) {
        $rExtra = "";
        if (isset($_POST["edit"])) {
            $rExtra = " AND `id` <> ".intval($_POST["edit"]);
        }
        $result = $db->query("SELECT `id` FROM `watch_folders` WHERE `directory` = '".$db->real_escape_string($rPath)."' AND `server_id` = ".intval($_POST["server_id"]).$rExtra.";");
        if (($result) && ($result->num_rows == 0)) {
            if (isset($_POST["edit"])) {
                $rArray = getWatchFolder($_POST["edit"]);
                unset($rArray["id"]);
            } else {
                $rArray = Array("directory" => "", "last_run" => 0, "server_id" => 0, "type" => "movie", "active" => 1);
            }
            $rArray["directory"] = $rPath;
            $rArray["server_id"] = intval($_POST["server_id"]);
            $rCols = $db->real_escape_string(implode(',', array_keys($rArray)));
            foreach (array_values($rArray) as $rValue) {
                isset($rValues) ? $rValues .= ',' : $rValues = '';
                if (is_array($rValue)) {
                    $rValue = json_encode($rValue);
                }
                if (is_null($rValue)) {
                    $rValues .= 'NULL';
                } else {
                    $rValues .= '\''.$db->real_escape_string($rValue).'\'';
                }
            }
            if (isset($_POST["edit"])) {
                $rCols = "id,".$rCols;
                $rValues = $_POST["edit"].",".$rValues;
            }
            $rQuery = "REPLACE INTO `watch_folders`(".$rCols.") VALUES(".$rValues.");";
            if ($db->query($rQuery)) {
                if (isset($_POST["edit"])) {
                    $rInsertID = intval($_POST["edit"]);
                } else {
                    $rInsertID = $db->insert_id;
                }
            }
            header("Location: ./movie_watch.php");exit;
        } else {
            $_STATUS = 1;
        }
    } else {
        $_STATUS = 0;
    }
}

if (isset($_GET["id"])) {
    $rFolder = getWatchFolder($_GET["id"]);
    if (!$rFolder) {
        exit;
    }
}

if ($rSettings["sidebar"]) {
    include "header_sidebar.php";
} else {
    include "header.php";
}
        if ($rSettings["sidebar"]) { ?>
        <div class="content-page"><div class="content boxed-layout"><div class="container-fluid">
        <?php } else { ?>
        <div class="wrapper boxed-layout"><div class="container-fluid">
        <?php } ?>
                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <a href="./movie_watch.php"><li class="breadcrumb-item"><i class="mdi mdi-backspace"></i> Back to Folder Watch</li></a>
                                </ol>
                            </div>
                            <h4 class="page-title"><?php if (isset($rFolder)) { echo "Edit"; } else { echo "Add"; } ?> Folder</h4>
                        </div>
                    </div>
                </div>     
                <!-- end page title --> 
                <div class="row">
                    <div class="col-xl-12">
                        <?php if ((isset($_STATUS)) && ($_STATUS == 0)) { ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            Please select a directory that isn't the root path of the server.
                        </div>
                        <?php } else if ((isset($_STATUS)) && ($_STATUS == 1)) { ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            The selected directory is already being watched. Please select another.
                        </div>
                        <?php } ?>
                        <div class="card">
                            <div class="card-body">
                                <form action="./movie_watch_folder.php<?php if (isset($_GET["id"])) { echo "?id=".$_GET["id"]; } ?>" method="POST" id="ip_form" data-parsley-validate="">
                                    <?php if (isset($rFolder)) { ?>
                                    <input type="hidden" name="edit" value="<?=$rFolder["id"]?>" />
                                    <?php } ?>
                                    <div id="basicwizard">
                                        <ul class="nav nav-pills bg-light nav-justified form-wizard-header mb-4">
                                            <li class="nav-item">
                                                <a href="#folder-details" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2"> 
                                                    <i class="mdi mdi-account-card-details-outline mr-1"></i>
                                                    <span class="d-none d-sm-inline">Details</span>
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content b-0 mb-0 pt-0">
                                            <div class="tab-pane" id="folder-details">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="server_id">Server Name</label>
                                                            <div class="col-md-8">
                                                                <select id="server_id" name="server_id" class="form-control" data-toggle="select2">
                                                                    <?php foreach (getStreamingServers() as $rServer) { ?>
                                                                    <option value="<?=$rServer["id"]?>"<?php if ((isset($rFolder)) && ($rFolder["server_id"] == $rServer["id"])) { echo " selected"; } ?>><?=$rServer["server_name"]?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="selected_path">Selected Path</label>
                                                            <div class="col-md-8 input-group">
                                                                <input type="text" id="selected_path" name="selected_path" class="form-control" value="<?php if (isset($rFolder)) { echo $rFolder["directory"]; } else { echo "/"; } ?>" required data-parsley-trigger="change">
                                                                <div class="input-group-append">
                                                                    <button class="btn btn-primary waves-effect waves-light" type="button" id="changeDir"><i class="mdi mdi-chevron-right"></i></button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <div class="col-md-6">
                                                                <table id="datatable" class="table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th width="20px"></th>
                                                                            <th>Directory</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody></tbody>
                                                                </table>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <table id="datatable-files" class="table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th width="20px"></th>
                                                                            <th>Filename</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody></tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end col -->
                                                </div> <!-- end row -->
                                                <ul class="list-inline wizard mb-0">
                                                    <li class="next list-inline-item float-right">
                                                        <input name="submit_folder" type="submit" class="btn btn-primary" value="<?php if (isset($rFolder)) { echo "Edit"; } else { echo "Add"; } ?>" />
                                                    </li>
                                                </ul>
                                            </div>
                                        </div> <!-- tab-content -->
                                    </div> <!-- end #basicwizard-->
                                </form>

                            </div> <!-- end card-body -->
                        </div> <!-- end card-->
                    </div> <!-- end col -->
                </div>
            </div> <!-- end container -->
        </div>
        <!-- end wrapper -->
        <?php if ($rSettings["sidebar"]) { echo "</div>"; } ?>
        <!-- Footer Start -->
        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 copyright text-center"><?=getFooter()?></div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->

        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/libs/jquery-toast/jquery.toast.min.js"></script>
        <script src="assets/libs/jquery-nice-select/jquery.nice-select.min.js"></script>
        <script src="assets/libs/switchery/switchery.min.js"></script>
        <script src="assets/libs/select2/select2.min.js"></script>
        <script src="assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>
        <script src="assets/libs/bootstrap-maxlength/bootstrap-maxlength.min.js"></script>
        <script src="assets/libs/clockpicker/bootstrap-clockpicker.min.js"></script>
        <script src="assets/libs/moment/moment.min.js"></script>
        <script src="assets/libs/daterangepicker/daterangepicker.js"></script>
        <script src="assets/libs/datatables/jquery.dataTables.min.js"></script>
        <script src="assets/libs/datatables/dataTables.bootstrap4.js"></script>
        <script src="assets/libs/datatables/dataTables.responsive.min.js"></script>
        <script src="assets/libs/datatables/responsive.bootstrap4.min.js"></script>
        <script src="assets/libs/datatables/dataTables.buttons.min.js"></script>
        <script src="assets/libs/datatables/buttons.bootstrap4.min.js"></script>
        <script src="assets/libs/datatables/buttons.html5.min.js"></script>
        <script src="assets/libs/datatables/buttons.flash.min.js"></script>
        <script src="assets/libs/datatables/buttons.print.min.js"></script>
        <script src="assets/libs/datatables/dataTables.keyTable.min.js"></script>
        <script src="assets/libs/datatables/dataTables.select.min.js"></script>
        <script src="assets/libs/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>
        <script src="assets/libs/parsleyjs/parsley.min.js"></script>
        <script src="assets/js/pages/form-wizard.init.js"></script>
        
        <script>
        function selectDirectory(elem) {
            window.currentDirectory += elem + "/";
            $("#selected_path").val(window.currentDirectory);
            $("#changeDir").click();
        }
        function selectParent() {
            $("#selected_path").val(window.currentDirectory.split("/").slice(0,-2).join("/") + "/");
            $("#changeDir").click();
        }
        
        $(document).ready(function() {
            $('select').select2({width: '100%'});
            
            $("#datatable").DataTable({
                responsive: false,
                paging: false,
                bInfo: false,
                searching: false,
                scrollY: "250px",
                columnDefs: [
                    {"className": "dt-center", "targets": [0]},
                ],
                "language": {
                    "emptyTable": ""
                }
            });
            
            $("#datatable-files").DataTable({
                responsive: false,
                paging: false,
                bInfo: false,
                searching: true,
                scrollY: "250px",
                columnDefs: [
                    {"className": "dt-center", "targets": [0]},
                ],
                "language": {
                    "emptyTable": "No compatible files found"
                }
            });
            
            $("#select_folder").click(function() {
                $("#import_folder").val("s:" + $("#server_id").val() + ":" + window.currentDirectory);
                $.magnificPopup.close();
            });
            
            $("#changeDir").click(function() {
                window.currentDirectory = $("#selected_path").val();
                if (window.currentDirectory.substr(-1) != "/") {
                    window.currentDirectory += "/";
                }
                $("#selected_path").val(window.currentDirectory);
                $("#datatable").DataTable().clear();
                $("#datatable").DataTable().row.add(["", "Loading..."]);
                $("#datatable").DataTable().draw(true);
                $("#datatable-files").DataTable().clear();
                $("#datatable-files").DataTable().row.add(["", "Please wait..."]);
                $("#datatable-files").DataTable().draw(true);
                $.getJSON("./api.php?action=listdir&dir=" + window.currentDirectory + "&server=" + $("#server_id").val() + "&filter=video", function(data) {
                    $("#datatable").DataTable().clear();
                    $("#datatable-files").DataTable().clear();
                    if (window.currentDirectory != "/") {
                        $("#datatable").DataTable().row.add(["<i class='mdi mdi-subdirectory-arrow-left'></i>", "Parent Directory"]);
                    }
                    if (data.result == true) {
                        $(data.data.dirs).each(function(id, dir) {
                            $("#datatable").DataTable().row.add(["<i class='mdi mdi-folder-open-outline'></i>", dir]);
                        });
                        $("#datatable").DataTable().draw(true);
                        $(data.data.files).each(function(id, dir) {
                            $("#datatable-files").DataTable().row.add(["<i class='mdi mdi-file-video'></i>", dir]);
                        });
                        $("#datatable-files").DataTable().draw(true);
                    }
                });
            });
            
            $('#datatable').on('click', 'tbody > tr', function() {
                if ($(this).find("td").eq(1).html() == "Parent Directory") {
                    selectParent();
                } else {
                    selectDirectory($(this).find("td").eq(1).html());
                }
            });
            
            $("#server_id").change(function() {
                $("#selected_path").val("/");
                $("#changeDir").click();
            });
            
            $("#changeDir").click();
            
            $(document).keypress(function(event){
                if (event.which == '13') {
                    event.preventDefault();
                }
            });
            $("form").attr('autocomplete', 'off');
        });
        </script>
        
        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
    </body>
</html>