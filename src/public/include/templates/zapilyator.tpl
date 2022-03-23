<?php
NFW::i()->registerResource('jquery.activeForm');

$speeds = array(
    array('value' => 0, 'desc' => 'Take from GIF'),
    array('value' => 1, 'desc' => '1 - fastest'),
    2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 15, 20, 30, 40, 50, 75, 100, 125, 150, 200, 255
);
?>
<script type="text/javascript">
    $(document).ready(function () {
        const downloadResultBtn = $('button[id="download-result"]');
        const resultLog = $('div[id="result-log"]');

        $('form[id="demo-maker"]').activeForm({
            'error': function (response) {
                handleResponse(response);
            },
            'success': function (response) {
                handleResponse(response);
            },
            'beforeSubmit': function () {
                resultLog.html('<div>Loading data...</div>');
                downloadResultBtn.attr('disabled', 'disabled');
                $('button[id="close-dialog"]').attr('disabled', 'disabled');
                $('div[id="result-dialog"]').modal('show');
            }
        });

        function handleResponse(response) {
            if (response.result === 'error') {
                resultLog.append('<div class="text-error">' + response["last_msg"] + '</div>');
                $('button[id="close-dialog"]').removeAttr('disabled');
                return;
            } else if (response.result === 'done') {
                // Append success messages
                $.each(response.log, function (i, text) {
                    resultLog.append('<div>' + text + '</div>');
                });
                resultLog.scrollTop(9999);


                $('button[id="close-dialog"]').removeAttr('disabled');
                downloadResultBtn.removeAttr('disabled').attr('href', response.download);
                return;
            }

            // Append log
            $.each(response.log, function (i, text) {
                $('div[id="result-log"]').append('<div>' + text + '</div>');
            });
            resultLog.scrollTop(9999);

            // Do next stage
            $.post(null, response, function (next_response) {
                handleResponse(next_response);
            }, 'json');
        }

        downloadResultBtn.click(function () {
            window.location.href = $(this).attr('href');
        });
    });
</script>

<style>
    FORM#demo-maker .form-group-margin {
        margin-bottom: 10px;
    }

    FORM#demo-maker LABEL.hint {
        font-weight: normal;
    }

    DIV#result-log {
        font-family: monospace;
        font-size: 85%;
        white-space: pre-wrap;
        height: 256px;
        max-height: 256px;
        overflow: scroll;
    }
</style>

<div id="result-dialog" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h4 class="modal-title">Result</h4></div>
            <div id="content" class="modal-body">
                <div id="result-log"></div>
            </div>
            <div class="modal-footer">
                <button id="download-result" class="btn btn-primary" disabled="disabled">Download</button>
                <button id="close-dialog" type="button" class="btn btn-default" data-dismiss="modal"
                        disabled="disabled">Close
                </button>
            </div>
        </div>
    </div>
</div>

<form id="demo-maker" class="form-horizontal" action="?action=make" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="16777216"/>
    <fieldset>
        <legend>Main setup</legend>
        <div class="form-group form-group-margin">
            <label for="musicFile" class="col-md-3 control-label">PT2/PT3 music</label>
            <div class="col-md-9">
                <input id="musicFile" type="file" name="music_file"/>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="splashBackground" class="col-md-3 control-label">Splash file</label>
            <div class="col-md-9">
                <input id="splashBackground" type="file" name="splash_background"/>
            </div>
        </div>

        <div class="form-group form-group-margin" id="border">
            <label for="splashBorder" class="col-md-3 control-label">Border</label>
            <div class="col-md-2">
                <select id="splashBorder" name="splash_border" class="form-control">
                    <option value="0" style="background-color: #000000;">black</option>
                    <option value="1" style="background-color: #0000cc;">blue</option>
                    <option value="2" style="background-color: #cc0000;">red</option>
                    <option value="3" style="background-color: #cc00cc;">magenta</option>
                    <option value="4" style="background-color: #00cc00;">green</option>
                    <option value="5" style="background-color: #00cccc;">cyan</option>
                    <option value="6" style="background-color: #cccc00;">yellow</option>
                    <option value="7" style="background-color: #cccccc;">white</option>
                </select>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="splashDelay" class="col-md-3 control-label">Splash delay</label>
            <div class="col-md-2">
                <select id="splashDelay" name="splash_delay" class="form-control">
                    <option value="1">1 pattern</option>
                    <option value="2">2 patterns</option>
                    <option value="3">3 patterns</option>
                    <option value="4">4 patterns</option>
                    <option value="5">5 patterns</option>
                </select>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="splashAnalyzerChannel" class="col-md-3 control-label">Analyzer / Sense</label>
            <div class="col-md-3">
                <select id="splashAnalyzerChannel" name="splash_analyzer_channel" class="form-control">
                    <option value="0">disabled</option>
                    <option value="8">A</option>
                    <option value="9">B</option>
                    <option value="10">C</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="splashAnalyzerSens" name="splash_analyzer_sens" class="form-control">
                    <option value="8">08</option>
                    <option value="9">09</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                    <option value="13">13</option>
                    <option value="14">14</option>
                    <option value="15">15</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="splashAnalyzerSens" class="hint">
                    Using FLASH color in Splash image for analyzer area
                </label>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Background setup</legend>

        <div class="form-group form-group-margin">
            <label for="mainBackground" class="col-md-3 control-label">Background</label>
            <div class="col-md-9">
                <input id="mainBackground" type="file" name="main_background"/>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="mainBorder" class="col-md-3 control-label">Animation Border</label>
            <div class="col-md-2">
                <select id="mainBorder" name="main_border" class="form-control">
                    <option value="0" style="background-color: #000000;">black</option>
                    <option value="1" style="background-color: #0000cc;">blue</option>
                    <option value="2" style="background-color: #cc0000;">red</option>
                    <option value="3" style="background-color: #cc00cc;">magenta</option>
                    <option value="4" style="background-color: #00cc00;">green</option>
                    <option value="5" style="background-color: #00cccc;">cyan</option>
                    <option value="6" style="background-color: #cccc00;">yellow</option>
                    <option value="7" style="background-color: #cccccc;">white</option>
                </select>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="mainInk" class="col-md-3 control-label">INK</label>
            <div class="col-md-2">
                <select id="mainInk" name="main_ink" class="form-control">
                    <option value="0" style="background-color: #000000;">black</option>
                    <option value="1" style="background-color: #0000cc;">blue</option>
                    <option value="2" style="background-color: #cc0000;">red</option>
                    <option value="3" style="background-color: #cc00cc;">magenta</option>
                    <option value="4" style="background-color: #00cc00;">green</option>
                    <option value="5" style="background-color: #00cccc;">cyan</option>
                    <option value="6" style="background-color: #cccc00;">yellow</option>
                    <option value="7" style="background-color: #cccccc;" selected="selected">white</option>
                </select>
            </div>
            <label for="mainPaper" class="col-md-1 control-label">PAPER</label>
            <div class="col-md-2">
                <select id="mainPaper" name="main_paper" class="form-control">
                    <option value="0" style="background-color: #000000;">black</option>
                    <option value="1" style="background-color: #0000cc;">blue</option>
                    <option value="2" style="background-color: #cc0000;">red</option>
                    <option value="3" style="background-color: #cc00cc;">magenta</option>
                    <option value="4" style="background-color: #00cc00;">green</option>
                    <option value="5" style="background-color: #00cccc;">cyan</option>
                    <option value="6" style="background-color: #cccc00;">yellow</option>
                    <option value="7" style="background-color: #cccccc;">white</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="checkbox-inline">
                    <input type="hidden" name="main_bright" value="0"/>
                    <input type="checkbox" name="main_bright" value="1" checked="checked"/> BRIGHT
                </label>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="mainAnalyzerChannel" class="col-md-3 control-label">Analyzer / Sense</label>
            <div class="col-md-3">
                <select id="mainAnalyzerChannel" name="main_analyzer_channel" class="form-control">
                    <option value="0">disabled</option>
                    <option value="8">A</option>
                    <option value="9">B</option>
                    <option value="10">C</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="mainAnalyzerSens" name="main_analyzer_sens" class="form-control">
                    <option value="8">08</option>
                    <option value="9">09</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                    <option value="13">13</option>
                    <option value="14">14</option>
                    <option value="15">15</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="mainAnalyzerSens" class="hint">
                    Using FLASH-color in Background image for analyzer area
                </label>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Animation 1 (main/slow)</legend>
        <div class="form-group" id="animation1">
            <label for="animation1" class="col-md-3 control-label">GIF/ZIP file</label>
            <div class="col-md-9">
                <input id="animation1" type="file" name="animation1"/>
                <span class="help-block"></span>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="speed1" class="col-md-3 control-label">Speed</label>
            <div class="col-md-3">
                <select id="speed1" name="speed1" class="form-control">
                    <?php foreach ($speeds as $s) {
                        if (is_array($s)) {
                            echo '<option value="' . $s['value'] . '">' . $s['desc'] . '</option>';
                        } else {
                            echo '<option value="' . $s . '">' . $s . '</option>';
                        }
                    } ?>
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Animation 2 (int/slow)</legend>
        <div class="form-group" id="animation2">
            <label for="animation2" class="col-md-3 control-label">GIF/ZIP file</label>
            <div class="col-md-9">
                <input id="animation2" type="file" name="animation2"/>
                <span class="help-block"></span>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="speed2" class="col-md-3 control-label">Speed</label>
            <div class="col-md-3">
                <select id="speed2" name="speed2" class="form-control">
                    <?php foreach ($speeds as $s) {
                        if (is_array($s)) {
                            echo '<option value="' . $s['value'] . '">' . $s['desc'] . '</option>';
                        } else {
                            echo '<option value="' . $s . '">' . $s . '</option>';
                        }
                    } ?>
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Animation 3 (int/fast)</legend>
        <div class="form-group" id="animation3">
            <label for="animation3" class="col-md-3 control-label">GIF/ZIP file</label>
            <div class="col-md-9">
                <input id="animation3" type="file" name="animation3"/>
                <span class="help-block"></span>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="speed3" class="col-md-3 control-label">Speed</label>
            <div class="col-md-3">
                <select id="speed3" name="speed3" class="form-control">
                    <?php foreach ($speeds as $s) {
                        if (is_array($s)) {
                            echo '<option value="' . $s['value'] . '">' . $s['desc'] . '</option>';
                        } else {
                            echo '<option value="' . $s . '">' . $s . '</option>';
                        }
                    } ?>
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Animation 4 (int/fast)</legend>
        <div class="form-group" id="animation4">
            <label for="animation4" class="col-md-3 control-label">GIF/ZIP file</label>
            <div class="col-md-9">
                <input id="animation4" type="file" name="animation4"/>
                <span class="help-block"></span>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="speed4" class="col-md-3 control-label">Speed</label>
            <div class="col-md-3">
                <select id="speed4" name="speed4" class="form-control">
                    <?php foreach ($speeds as $s) {
                        if (is_array($s)) {
                            echo '<option value="' . $s['value'] . '">' . $s['desc'] . '</option>';
                        } else {
                            echo '<option value="' . $s . '">' . $s . '</option>';
                        }
                    } ?>
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Scroll setup</legend>
        <div class="form-group form-group-margin">
            <label for="scrollText" class="col-md-3 control-label">Text</label>
            <div class="col-md-9">
                <textarea id="scrollText" name="scroll_text" class="form-control" rows="7"></textarea>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label class="col-md-3 control-label">Scroll font</label>
            <div class="col-md-9">
                <label class="radio-inline">
                    <input type="radio" checked name="scroll_font" value="1"/>
                    <img src="<?php echo NFW::i()->base_path ?>resources/zapilyator/font1.png" alt=""/>
                </label>
                <label class="radio-inline">
                    <input type="radio" name="scroll_font" value="2"/>
                    <img src="<?php echo NFW::i()->base_path ?>resources/zapilyator/font2.png" alt=""/>
                </label>
                <label class="radio-inline">
                    <input type="radio" name="scroll_font" value="3"/>
                    <img src="<?php echo NFW::i()->base_path ?>resources/zapilyator/font3.png" alt=""/>
                </label>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="scrollInk" class="col-md-3 control-label">INK</label>
            <div class="col-md-2">
                <select id="scrollInk" name="scroll_ink" class="form-control">
                    <option value="0" style="background-color: #000000;">black</option>
                    <option value="1" style="background-color: #0000cc;">blue</option>
                    <option value="2" style="background-color: #cc0000;">red</option>
                    <option value="3" style="background-color: #cc00cc;">magenta</option>
                    <option value="4" style="background-color: #00cc00;">green</option>
                    <option value="5" style="background-color: #00cccc;">cyan</option>
                    <option value="6" style="background-color: #cccc00;" selected="selected">yellow</option>
                    <option value="7" style="background-color: #cccccc;">white</option>
                </select>
            </div>
            <label for="scrollPaper" class="col-md-1 control-label">PAPER</label>
            <div class="col-md-2">
                <select id="scrollPaper" name="scroll_paper" class="form-control">
                    <option value="0" style="background-color: #000000;">black</option>
                    <option value="1" style="background-color: #0000cc;">blue</option>
                    <option value="2" style="background-color: #cc0000;">red</option>
                    <option value="3" style="background-color: #cc00cc;">magenta</option>
                    <option value="4" style="background-color: #00cc00;">green</option>
                    <option value="5" style="background-color: #00cccc;">cyan</option>
                    <option value="6" style="background-color: #cccc00;">yellow</option>
                    <option value="7" style="background-color: #cccccc;">white</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="checkbox-inline">
                    <input type="hidden" name="scroll_bright" value="0"/>
                    <input type="checkbox" name="scroll_bright" value="1" checked="checked"/> BRIGHT
                </label>
            </div>
        </div>

        <div class="form-group form-group-margin">
            <label for="scrollPosition" class="col-md-3 control-label">Position</label>
            <div class="col-md-3">
                <select id="scrollPosition" name="scroll_position" class="form-control">
                    <option value="#401f|#5800">01</option>
                    <option value="#403f|#5820">02</option>
                    <option value="#405f|#5840">03</option>
                    <option value="#407f|#5860">04</option>
                    <option value="#409f|#5880">05</option>
                    <option value="#40bf|#58a0">06</option>
                    <option value="#40de|#58c0">07</option>
                    <option value="#40ff|#58e0">08</option>

                    <option value="#481f|#5900">09</option>
                    <option value="#483f|#5920">10</option>
                    <option value="#485f|#5940">11</option>
                    <option value="#487f|#5960">12</option>
                    <option value="#489f|#5980">13</option>
                    <option value="#48bf|#59a0">14</option>
                    <option value="#48df|#59c0">15</option>
                    <option value="#48ff|#59e0">16</option>

                    <option value="#501f|#5a00">17</option>
                    <option value="#503f|#5a20">18</option>
                    <option value="#505f|#5a40">19</option>
                    <option value="#507f|#5a60">20</option>
                    <option value="#509f|#5a80">21</option>
                    <option value="#50bf|#5aa0">22</option>
                    <option value="#50df|#5ac0">23</option>
                </select>
            </div>
        </div>
        <br/>
        <div class="form-group">
            <div class="col-md-offset-3 col-md-9">
                <button type="submit" class="btn btn-primary btn-lg">Create</button>
            </div>
        </div>
    </fieldset>
</form>