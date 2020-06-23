<?php
include "functions.php";
if (!isset($_SESSION['user_id'])) { header("Location: ./login.php"); exit; }
if (!$rPermissions["is_admin"]) { exit; }

$rCategories = getCategories("movie");

if (isset($_POST["submit_stream"])) {
    if (isset($_POST["edit"])) {
        $rArray = getStream($_POST["edit"]);
        unset($rArray["id"]);
    } else {
        $rArray = Array("type" => 2, "target_container" => Array("mp4"), "added" => time(), "read_native" => 0, "stream_all" => 0, "redirect_stream" => 1, "direct_source" => 1, "gen_timestamps" => 0, "transcode_attributes" => Array(), "stream_display_name" => "", "stream_source" => Array(), "category_id" => 0, "stream_icon" => "", "notes" => "", "custom_sid" => "", "custom_ffmpeg" => "", "transcode_profile_id" => 0, "enable_transcode" => 0, "auto_restart" => "[]", "allow_record" => 0, "rtmp_output" => 0, "epg_id" => null, "channel_id" => null, "epg_lang" => null, "tv_archive_server_id" => 0, "tv_archive_duration" => 0, "delay_minutes" => 0, "external_push" => Array(), "probesize_ondemand" => 128000);
    }
    $rArray["stream_display_name"] = $_POST["stream_display_name"];
    $rArray["stream_source"] = Array($_POST["stream_source"]);
    $rArray["notes"] = $_POST["notes"];
    $rArray["target_container"] = Array($_POST["target_container"]);
    $rArray["category_id"] = $_POST["category_id"];
    if (strlen($_POST["tmdb_id"]) > 0) {
        $rTMDBURL = "https://www.themoviedb.org/movie/".$_POST["tmdb_id"];
    } else {
        $rTMDBURL = "";
    }
    $rGenre = $rCategories[$_POST["category_id"]]["category_name"];
    $rSeconds = intval($_POST["episode_run_time"]) * 60;
    $rArray["movie_propeties"] = Array("kinopoisk_url" => $rTMDBURL, "tmdb_id" => $_POST["tmdb_id"], "name" => $rArray["stream_display_name"], "o_name" => $rArray["stream_display_name"], "cover_big" => $_POST["cover_big"], "movie_image" => $_POST["cover_big"], "releasedate" => $_POST["releasedate"], "episode_run_time" => $_POST["episode_run_time"], "youtube_trailer" => $_POST["youtube_trailer"], "director" => $_POST["director"], "actors" => $_POST["cast"], "cast" => $_POST["cast"], "description" => $_POST["plot"], "plot" => $_POST["plot"], "age" => "", "mpaa_rating" => "", "rating_count_kinopoisk" => 0, "country" => $_POST["country"], "genre" => $rGenre, "backdrop_path" => Array($_POST["backdrop_path"]), "duration_secs" => $rSeconds, "duration" => sprintf('%02d:%02d:%02d', ($rSeconds/3600),($rSeconds/60%60), $rSeconds%60), "video" => Array(), "audio" => Array(), "bitrate" => 0, "rating" => $_POST["rating"]);
    $rCols = "`".implode('`,`', array_keys($rArray))."`";
    $rValues = null;
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
        $rCols = "`id`,".$rCols;
        $rValues = $_POST["edit"].",".$rValues;
    }
    $rQuery = "REPLACE INTO `streams`(".$rCols.") VALUES(".$rValues.");";
    if ($db->query($rQuery)) {
        if (isset($_POST["edit"])) {
            $rInsertID = intval($_POST["edit"]);
        } else {
            $rInsertID = $db->insert_id;
        }
    }
    if (isset($rInsertID)) {
        $_GET["id"] = $rInsertID;
        $_STATUS = 0;
    } else {
        $_STATUS = 1;
    }
}

if (isset($_GET["id"])) {
    $rMovie = getStream($_GET["id"]);
    if (!$rMovie) {
        exit;
    }
    $rMovie["properties"] = json_decode($rMovie["movie_propeties"], True);
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
                                    <a href="./movies.php"><li class="breadcrumb-item"><i class="mdi mdi-backspace"></i> Back to Movies</li></a>
                                </ol>
                            </div>
                            <h4 class="page-title"><?php if (isset($rMovie)) { echo $rMovie["stream_display_name"]; } else { echo "Add Movie"; } ?></h4>
                        </div>
                    </div>
                </div>     
                <!-- end page title --> 
                <div class="row">
                    <div class="col-xl-12">
                        <?php if ((isset($_STATUS)) && ($_STATUS == 0)) { ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            Movie operation was completed successfully.
                        </div>
                        <?php } else if ((isset($_STATUS)) && ($_STATUS > 0)) { ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            There was an error performing this operation! Please check the form entry and try again.
                        </div>
                        <?php } ?>
                        <div class="card">
                            <div class="card-body">
                                <form action="./movie.php<?php if (isset($_GET["id"])) { echo "?id=".$_GET["id"]; } ?>" method="POST" id="stream_form">
                                    <?php if (isset($rMovie)) { ?>
                                    <input type="hidden" name="edit" value="<?=$rMovie["id"]?>" />
                                    <?php } ?>
                                    <input type="hidden" id="tmdb_id" name="tmdb_id" value="<?php if (isset($rMovie)) { echo $rMovie["properties"]["tmdb_id"]; } ?>" />
                                    <div id="basicwizard">
                                        <ul class="nav nav-pills bg-light nav-justified form-wizard-header mb-4">
                                            <li class="nav-item">
                                                <a href="#stream-details" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2"> 
                                                    <i class="mdi mdi-account-card-details-outline mr-1"></i>
                                                    <span class="d-none d-sm-inline">Details</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#movie-information" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                                    <i class="mdi mdi-folder-alert-outline mr-1"></i>
                                                    <span class="d-none d-sm-inline">Movie Information</span>
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content b-0 mb-0 pt-0">
                                            <div class="tab-pane" id="stream-details">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p class="sub-header">
                                                            The VOD system currently allows direct sources only, future updates will improve on this and provide encoding, file browser and further options.
                                                        </p>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="stream_display_name">Movie Name</label>
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control" id="stream_display_name" name="stream_display_name" value="<?php if (isset($rMovie)) { echo $rMovie["stream_display_name"]; } ?>">
                                                            </div>
                                                        </div>
                                                        <span class="streams">
                                                            <?php
                                                            if (isset($rMovie)) {
                                                                $rMovieSource = json_decode($rMovie["stream_source"], True)[0];
                                                            } else {
                                                                $rMovieSource = "";
                                                            } ?>
                                                            <div class="form-group row mb-4 stream-url">
                                                                <label class="col-md-4 col-form-label" for="stream_source"> Movie URL</label>
                                                                <div class="col-md-8 input-group">
                                                                    <input type="text" id="stream_source" name="stream_source" class="form-control" value="<?=$rMovieSource?>">
                                                                </div>
                                                            </div>
                                                        </span>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="category_id">Category Name</label>
                                                            <div class="col-md-8">
                                                                <select name="category_id" id="category_id" class="form-control" data-toggle="select2">
                                                                    <?php foreach ($rCategories as $rCategory) { ?>
                                                                    <option <?php if (isset($rMovie)) { if (intval($rMovie["category_id"]) == intval($rCategory["id"])) { echo "selected "; } } else if ((isset($_GET["category"])) && ($_GET["category"] == $rCategory["id"])) { echo "selected "; } ?>value="<?=$rCategory["id"]?>"><?=$rCategory["category_name"]?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="notes">Notes</label>
                                                            <div class="col-md-8">
                                                                <textarea id="notes" name="notes" class="form-control" rows="3" placeholder=""><?php if (isset($rMovie)) { echo $rMovie["notes"]; } ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="target_container">Target Container</label>
                                                            <div class="col-md-2">
                                                                <select name="target_container" id="target_container" class="form-control" data-toggle="select2">
                                                                    <?php foreach (Array("mp4", "mkv") as $rContainer) { ?>
                                                                    <option <?php if (isset($rMovie)) { if ($rMovie["target_container"] == $rContainer) { echo "selected "; } } ?>value="<?=$rContainer?>"><?=$rContainer?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div> <!-- end col -->
                                                </div> <!-- end row -->
                                                <ul class="list-inline wizard mb-0">
                                                    <li class="next list-inline-item float-right">
                                                        <a href="javascript: void(0);" class="btn btn-secondary">Next</a>
                                                    </li>
                                                </ul>
                                            </div>

                                            <div class="tab-pane" id="movie-information">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="tmdb_search">TMDb Results</label>
                                                            <div class="col-md-8">
                                                                <select id="tmdb_search" class="form-control" data-toggle="select2"></select>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="cover_big">Poster URL</label>
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control" id="cover_big" name="cover_big" value="<?php if (isset($rMovie)) { echo $rMovie["properties"]["cover_big"]; } ?>">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="backdrop_path">Backdrop URL</label>
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control" id="backdrop_path" name="backdrop_path" value="<?php if (isset($rMovie)) { echo $rMovie["properties"]["backdrop_path"][0]; } ?>">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="plot">Plot</label>
                                                            <div class="col-md-8">
                                                                <textarea rows="6" class="form-control" id="plot" name="plot"><?php if (isset($rMovie)) { echo $rMovie["properties"]["plot"]; } ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="director">Director</label>
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control" id="director" name="director" value="<?php if (isset($rMovie)) { echo $rMovie["properties"]["director"]; } ?>">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="cast">Cast</label>
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control" id="cast" name="cast" value="<?php if (isset($rMovie)) { echo $rMovie["properties"]["cast"]; } ?>">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="releasedate">Release Date</label>
                                                            <div class="col-md-3">
                                                                <input type="text" class="form-control" id="releasedate" name="releasedate" value="<?php if (isset($rMovie)) { echo $rMovie["properties"]["releasedate"]; } ?>">
                                                            </div>
                                                            <label class="col-md-2 col-form-label" for="episode_run_time">Runtime</label>
                                                            <div class="col-md-3">
                                                                <input type="text" class="form-control" id="episode_run_time" name="episode_run_time" value="<?php if (isset($rMovie)) { echo $rMovie["properties"]["episode_run_time"]; } ?>">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="youtube_trailer">Trailer URL</label>
                                                            <div class="col-md-3">
                                                                <input type="text" class="form-control" id="youtube_trailer" name="youtube_trailer" value="<?php if (isset($rMovie)) { echo $rMovie["properties"]["youtube_trailer"]; } ?>">
                                                            </div>
                                                            <label class="col-md-2 col-form-label" for="rating">Rating</label>
                                                            <div class="col-md-3">
                                                                <input type="text" class="form-control" id="rating" name="rating" value="<?php if (isset($rMovie)) { echo $rMovie["properties"]["rating"]; } ?>">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row mb-4">
                                                            <label class="col-md-4 col-form-label" for="country">Country</label>
                                                            <div class="col-md-8">
                                                                <input type="text" class="form-control" id="country" name="country" value="<?php if (isset($rMovie)) { echo $rMovie["properties"]["country"]; } ?>">
                                                            </div>
                                                        </div>
                                                    </div> <!-- end col -->
                                                </div> <!-- end row -->
                                                <ul class="list-inline wizard mb-0">
                                                    <li class="previous list-inline-item">
                                                        <a href="javascript: void(0);" class="btn btn-secondary">Previous</a>
                                                    </li>
                                                    <li class="next list-inline-item float-right">
                                                        <input name="submit_stream" type="submit" class="btn btn-primary" value="<?php if (isset($rMovie)) { echo "Edit"; } else { echo "Add"; } ?>" />
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
        <!-- file preview template -->
        <div class="d-none" id="uploadPreviewTemplate">
            <div class="card mt-1 mb-0 shadow-none border">
                <div class="p-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <img data-dz-thumbnail class="avatar-sm rounded bg-light" alt="">
                        </div>
                        <div class="col pl-0">
                            <a href="javascript:void(0);" class="text-muted font-weight-bold" data-dz-name></a>
                            <p class="mb-0" data-dz-size></p>
                        </div>
                        <div class="col-auto">
                            <!-- Button -->
                            <a href="" class="btn btn-link btn-lg text-muted" data-dz-remove>
                                <i class="mdi mdi-close-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Start -->
        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 copyright text-center"><?=getFooter()?></div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->

        <!-- Vendor js -->
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/libs/jquery-toast/jquery.toast.min.js"></script>
        <script src="assets/libs/jquery-nice-select/jquery.nice-select.min.js"></script>
        <script src="assets/libs/switchery/switchery.min.js"></script>
        <script src="assets/libs/select2/select2.min.js"></script>
        <script src="assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js"></script>
        <script src="assets/libs/bootstrap-maxlength/bootstrap-maxlength.min.js"></script>
        <script src="assets/libs/clockpicker/bootstrap-clockpicker.min.js"></script>
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

        <!-- Plugins js-->
        <script src="assets/libs/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>

        <!-- Tree view js -->
        <script src="assets/libs/treeview/jstree.min.js"></script>
        <script src="assets/js/pages/treeview.init.js"></script>
        <script src="assets/js/pages/form-wizard.init.js"></script>

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
        
        <script>
        var changeTitle = false;
        
        (function($) {
          $.fn.inputFilter = function(inputFilter) {
            return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
              if (inputFilter(this.value)) {
                this.oldValue = this.value;
                this.oldSelectionStart = this.selectionStart;
                this.oldSelectionEnd = this.selectionEnd;
              } else if (this.hasOwnProperty("oldValue")) {
                this.value = this.oldValue;
                this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
              }
            });
          };
        }(jQuery));
        
        $(document).ready(function() {
            $('select').select2({width: '100%'})
            var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
            elems.forEach(function(html) {
              var switchery = new Switchery(html);
            });
            
            $("#stream_form").submit(function(e){
                if ($("#stream_display_name").val().length == 0) {
                    e.preventDefault();
                    $.toast("Enter a movie name.");
                }
                if ($("#stream_source").val().length == 0) {
                    e.preventDefault();
                    $.toast("Enter a movie source.");
                }
            });
            
            $(document).keypress(function(event){
                if (event.which == '13') {
                    event.preventDefault();
                }
            });
            
            $("#stream_display_name").change(function() {
                if (!window.changeTitle) {
                    $("#tmdb_search").empty().trigger('change');
                    if ($("#stream_display_name").val().length > 0) {
                        $.getJSON("./api.php?action=tmdb_search&type=movie&term=" + $("#stream_display_name").val(), function(data) {
                            if (data.result == true) {
                                if (data.data.results.length > 0) {
                                    newOption = new Option("Found " + data.data.results.length + " results", -1, true, true);
                                } else {
                                    newOption = new Option("No results found", -1, true, true);
                                }
                                $("#tmdb_search").append(newOption).trigger('change');
                                $(data.data.results).each(function(id, item) {
                                    newOption = new Option(item.title, item.id, true, true);
                                    $("#tmdb_search").append(newOption);
                                });
                            } else {
                                newOption = new Option("No results found", -1, true, true);
                            }
                            $("#tmdb_search").val(-1).trigger('change');
                        });
                    }
                } else {
                    window.changeTitle = false;
                }
            });
            $("#tmdb_search").change(function() {
                if (($("#tmdb_search").val()) && ($("#tmdb_search").val() > -1)) {
                    $.getJSON("./api.php?action=tmdb&type=movie&id=" + $("#tmdb_search").val(), function(data) {
                        if (data.result == true) {
                            window.changeTitle = true;
                            $("#stream_display_name").val(data.data.title);
                            $("#cover_big").val("");
                            if (data.data.poster_path.length > 0) {
                                $("#cover_big").val("https://image.tmdb.org/t/p/w600_and_h900_bestv2" + data.data.poster_path);
                            }
                            $("#backdrop_path").val("");
                            if (data.data.backdrop_path.length > 0) {
                                $("#backdrop_path").val("https://image.tmdb.org/t/p/w1280" + data.data.backdrop_path);
                            }
                            $("#releasedate").val(data.data.release_date);
                            $("#episode_run_time").val(data.data.runtime);
                            $("#youtube_trailer").val("");
                            $(data.data.videos.youtube).each(function(id, youtube) {
                                if (youtube.type == "Trailer") {
                                    $("#youtube_trailer").val(youtube.source);
                                }
                            });
                            rCast = "";
                            rMemberID = 0;
                            $(data.data.cast.cast).each(function(id, member) {
                                rMemberID += 1;
                                if (rMemberID <= 5) {
                                    if (rCast.length > 0) {
                                        rCast += ", ";
                                    }
                                    rCast += member.name;
                                }
                            });
                            $("#director").val("");
                            $(data.data.cast.crew).each(function(id, member) {
                                if (member.department == "Directing") {
                                    $("#director").val(member.name);
                                    return true;
                                }
                            });
                            $("#cast").val(rCast);
                            $("#country").val("");
                            $("#plot").val(data.data.overview);
                            if (data.data.production_countries.length > 0) {
                                $("#country").val(data.data.production_countries[0].name);
                            }
                            $("#rating").val(data.data.vote_average);
                            $("#tmdb_id").val(data.data.id);
                        }
                    });
                }
            });
            
            $("#runtime").inputFilter(function(value) { return /^\d*$/.test(value); });
            $("form").attr('autocomplete', 'off');
            
            $("#stream_display_name").val($("#stream_display_name").val());
        });
        </script>
    </body>
</html>