<?php
require_once("_basic_tasks.php");
require_once("_settings.php");
$url_this_page = "produce.php";
$this_title = "BP console";
require_once("_header.php");

$application_path = $bp_application_path;

if(isset($_GET['startup'])) $startup = $_GET['startup'];
else $startup = '';

if(isset($_GET['instruction'])) $instruction = $_GET['instruction'];
else $instruction = '';
if($instruction == '') {
	echo "ERROR: No instruction has been sent";
	die();
	}
if(isset($_GET['here'])) $here = urldecode($_GET['here']);
else $here = '???';
if(isset($_GET['csound_file'])) $csound_file = $_GET['csound_file'];
else $csound_file = '';

$max_sleep_time_after_bp_command = 15;
$check_command_line = FALSE;
$sound_file_link = $result_file = '';

if($instruction == "help") {
	$command = $application_path."bp --help";
	}
else {
	if(isset($_GET['grammar'])) $grammar_path = urldecode($_GET['grammar']);
	else $grammar_path = '';
	if($grammar_path == '') die();
	if(isset($_GET['settings_file'])) $settings_file = $_GET['settings_file'];
	else $settings_file = '';
	if(isset($_GET['objects_file'])) $objects_file = $_GET['objects_file'];
	else $objects_file = '';
	if(isset($_GET['note_convention'])) $note_convention = $_GET['note_convention'];
	else $note_convention = '';
	if(isset($_GET['alphabet'])) $alphabet_file = urldecode($_GET['alphabet']);
	else $alphabet_file = '';
	if(isset($_GET['format'])) $file_format = $_GET['format'];
	else $file_format = '';
	if($file_format <> '' AND isset($_GET['output'])) $output = urldecode($_GET['output']);
	else $output = '';
	if(isset($_GET['show_production'])) $show_production = TRUE;
	else $show_production = FALSE;
	if(isset($_GET['trace_production'])) $trace_production = TRUE;
	else $trace_production = FALSE;
	$new_random_seed =  FALSE;
	if(isset($_GET['random_seed'])) {
		$random_seed = $_GET['random_seed'];
		$new_random_seed = TRUE;
		}
	if(isset($_GET['csound_orchestra'])) $csound_orchestra = $_GET['csound_orchestra'];
	else $csound_orchestra = '';
	if(isset($_GET['test'])) $check_command_line = TRUE;
	
	$table = explode('/',$grammar_path);
	$grammar_name = $table[count($table) - 1];
	$dir = str_replace($grammar_name,'',$grammar_path);
	
	$grammar_name = str_replace(" ","_",$grammar_name);
//	echo "grammar_name = ".$grammar_name."<br />";
	$project_name = preg_replace("/\.[a-z]+$/u",'',$output);
	$result_file = $project_name."-result.html";
	@unlink($result_file);

	if($output <> '') @unlink($output);
	if($tracefile <> '') @unlink($tracefile);

	$thisgrammar = $grammar_path;
	if(is_integer(strpos($thisgrammar,' ')))
		$thisgrammar = '"'.$thisgrammar.'"';
	$command = $application_path."bp ".$instruction." -gr ".$thisgrammar;
	
	if(is_integer(strpos($output,' ')))
		$output = '"'.$output.'"';

	$thisalphabet = $alphabet_file;
	if(is_integer(strpos($thisalphabet,' ')))
		$thisalphabet = '"'.$thisalphabet.'"';
	$thisalphabet = $dir.$thisalphabet;

	if($alphabet_file <> '') $command .= " -ho ".$thisalphabet;

	if($note_convention <> '') $command .= " --".$note_convention;
	if($settings_file <> '') $command .= " -se ".$dir.$settings_file;
	if($csound_file <> '') $command .= " -cs ".$dir.$csound_file;
	if($objects_file <> '') $command .= " -mi ".$dir.$objects_file;
	
	if($startup <> '') $command .= " --start ".$startup;
	if($instruction == "produce" OR $instruction == "produce-all") {
		switch($file_format) {
			case "data":
				$command .= " -d -o ".$output;
				break;
			case "midi":
				$command .= " -d --midiout ".$output;
				break;
			case "csound":
				$command .= " -d --csoundout ".$output;
				break;
			default:
				$command .= " -d --rtmidi";
				break;
			}
		}
	if($tracefile <> '') $command .= " --traceout ".$tracefile;
	if($show_production) $command .= " --show-production";
	if($trace_production) $command .= " --trace-production";
	if($new_random_seed) $command .= " --seed ".$random_seed;
	}

if($instruction <> "help") {
	$dircontent = scandir($temp_dir);
	foreach($dircontent as $thisfile) {
		$time_saved = filemtime($temp_dir.$thisfile);
	//	echo $thisfile." ➡ ".date('Y-m-d H\hi',$time_saved)."<br />";
		$table = explode('_',$thisfile);
		if($table[0] <> "trace") continue;
		if(!isset($table[2]) OR $table[1] <> session_id()) continue;
		$found = FALSE; $this_name = '';
		for($i = 2; $i < (count($table) - 1); $i++) {
			if($table[$i] == "image") {
				$found = TRUE; break;
				}
			else {
				if($this_name == '') $this_name .= $table[$i];
				else $this_name .= "_".$table[$i];
				}
			}
		// Delete this image to be replaced with the current one
		if($this_name == $grammar_name) {
			$rep = @unlink($temp_dir.$thisfile);
			// Make sure deletion is complete before launcjhing the command
			$time_start = time();
			$time_end = $time_start + 5;
			if($rep) while(TRUE) {
				if(!file_exists($temp_dir.$thisfile)) break;
				if(time() > $time_end) break;
				sleep(1);
				}
			}
		}
	}

if($check_command_line) {
	echo "<p><i>Run this command in the “php” folder:</i></p>";
	echo "<p><font color=\"red\">➡</font> ".$command."</p>";
	die();
	}
echo "<p><small>command = <font color=\"red\">".$command."</font></small></p>";

if($instruction <> "help") {
	if($csound_file <> '') {
		$lock_file = $dir.$csound_file."_lock";
		$time_start = time();
		$time_end = $time_start + 5;
		while(TRUE) {
			if(!file_exists($lock_file)) break;
			if(time() > $time_end) {
				echo "<p><font color=\"red\">Maximum time (5 seconds) spent waiting for the Csound instrument file to be unlocked:</font> <font color=\"blue\">".$dir.$csound_file."</font></p>";
				break;
				}
			}
		}
	if($objects_file <> '') {
		$lock_file = $dir.$objects_file."_lock";
		$time_start = time();
		$time_end = $time_start + 5;
		while(TRUE) {
			if(!file_exists($lock_file)) break;
			if(time() > $time_end) {
				echo "<p><font color=\"red\">Maximum time (5 seconds) spent waiting for the sound-object prototypes file to be unlocked:</font> <font color=\"blue\">".$dir.$objects_file."</font></p>";
				break;
				}
			}
		}
	}
		
$o = send_to_console($command);
$n_messages = count($o);
$no_error = FALSE;
for($i = 0; $i < $n_messages; $i++) {
	$mssg = $o[$i];
	if(is_integer($pos=strpos($mssg,"Errors: 0")) AND $pos == 0) $no_error = TRUE;
	}
echo "<hr>";

if($instruction == "help") {
	for($i=0; $i < $n_messages; $i++) {
		$mssg = $o[$i];
		$mssg = clean_up_encoding(FALSE,TRUE,$mssg);
		echo $mssg."<br />";
		}
	die();
	}

if($instruction <> "help") {
	$time_start = time();
	$time_end = $time_start + $max_sleep_time_after_bp_command;
	$donefile = $temp_dir."trace_".session_id()."_done.txt";
//	echo $donefile."<br />";
	while(TRUE) {
		if(file_exists($donefile)) break;
		if(time() > $time_end) {
			echo "<p><font color=\"red\">Maximum time (".$max_sleep_time_after_bp_command." seconds) spent waiting for the 'done.txt' file… The process is incomplete!</font></p>";
			break;
			}
		}
	@unlink($donefile);
	$tracefile_html = clean_up_file_to_html($tracefile);
	$trace_link = $tracefile_html;
	$output_link = $output;
//	$test = TRUE;
	if($test) echo "output = ".$output."<br />";
	if($test) echo "tracefile_html = ".$tracefile_html."<br />";
	if($test) echo "dir = ".$dir."<br />";
	if($test) echo "trace_link = ".$trace_link."<br />";
	if($test) echo "output_link = ".$output_link."<br />";
	if($test) echo "file_format = ".$file_format."<br />";

	if(!$no_error) {
		echo "<p><font color=\"red\">Errors found… Check the </font> <a onclick=\"window.open('".$trace_link."','errors','width=800,height=800,left=400'); return false;\" href=\"".$trace_link."\">error trace</a> file!</p>";
		}
	else {
		echo "<p>";
		if($output <> '' AND $file_format <> "midi") {
			if($file_format <> "csound") {
				$output_html = clean_up_file_to_html($output);
				$output_link = $output_html;
				}
			echo "<font color=\"red\">➡</font> Read the <a onclick=\"window.open('".$output_link."','".$file_format."','width=800,height=700,left=300'); return false;\" href=\"".$output_link."\">output file</a> (or <a href=\"".$output_link."\" download>download it</a>)<br />";
			}
		if($trace_production OR $instruction == "templates" OR $show_production OR $trace_production) echo "<font color=\"red\">➡</font> Read the <a onclick=\"window.open('".$trace_link."','trace','width=800,height=600,left=400'); return false;\" href=\"".$trace_link."\">trace file</a> (or <a href=\"".$trace_link."\" download>download it</a>)";
		echo "</p>";
		 
		// Show MIDI file
		if($file_format == "midi") {
			$midi_file_link = $output;
			if(file_exists($midi_file_link)) {
		//		echo "midi_file_link = ".$midi_file_link."<br />";
				echo "<p class=\"shadow\" style=\"width:50%;\"><a href=\"#midi\" onClick=\"MIDIjs.play('".$midi_file_link."');\"><img src=\"pict/loudspeaker.png\" width=\"70px;\" style=\"vertical-align:middle;\" />Play MIDI file</a>";
				echo " (<a href=\"#midi\" onClick=\"MIDIjs.stop();\">Stop playing</a>)";
				echo "&nbsp;or <a href=\"".$midi_file_link."\" download>download it</a></p>";
				}
			}
		
		// Prepare images if any
		$dircontent = scandir($temp_dir);
		echo "<table style=\"background-color:snow; padding:0px;\"><tr>";
		$number_images = 0;
		foreach($dircontent as $thisfile) {
			$table = explode('_',$thisfile);
			if($table[0] <> "trace") continue;
			if(!isset($table[2]) OR $table[1] <> session_id()) continue;
			$found = FALSE; $this_name = '';
			for($i = 2; $i < (count($table) - 1); $i++) {
				if($table[$i] == "image") {
					$found = TRUE; break;
					}
				else {
					if($this_name == '') $this_name .= $table[$i];
					else $this_name .= "_".$table[$i];
					}
				}
		//	echo "this_name = ".$this_name."<br />";
			if(!$found OR $this_name <> $grammar_name OR isset($table[$i + 2])) continue;
			echo "<td style=\"background-color:white; border-radius: 6px; border: 4px solid Gold; vertical-align:middle; text-align: center; padding:8px; margin:0px;\>";
			$number = intval(str_replace(".html",'',$table[$i + 1]));
			$content = @file_get_contents($temp_dir.$thisfile,FALSE);
			$table2 = explode(chr(10),$content);
			$imax = count($table2);
			$table3 = array();
			$title1 = $grammar_name."_Image_".$number;
			$title2 = $grammar_name." Image ".$number;
			$WidthMax = $HeightMax = 0;
			$number_images++;
			for($i = 0; $i < $imax; $i++) {
				$line = trim($table2[$i]);
			//	echo $i." ".recode_tags($line)."<br />";
				if(is_integer($pos=strpos($line,"canvas.width"))) {
					$table4 = explode("=",$line);
					$WidthMax = round(intval($table4[2])) + 10;
				//	echo $i.") WidthMax = ".$WidthMax."<br />";
					}
				if(is_integer($pos=strpos($line,"canvas.height"))) {
					$table4 = explode("=",$line);
					$HeightMax = round(intval($table4[2]));
				//	echo $i.") HeightMax = ".$HeightMax."<br />";
					}
				}
			for($i = $j = 0; $i < $imax; $i++) {
				$line = trim($table2[$i]);
				$table3[$j] = $line;
				$j++;
				}
			$link = $temp_dir.$thisfile;
			$left = 100 + (400 * ($number - 1));
			$window_height = 600;
			if($HeightMax < $window_height) $window_height = $HeightMax + 60;
			$window_width = 1200;
			if($WidthMax < $window_width) $window_width = $WidthMax +  20;
			echo "<div style=\"border:2px solid gray; background-color:azure; width:8em;  padding:2px; text-align:center; border-radius: 6px;\"><a onclick=\"window.open('".$link."','".$title1."','width=".$window_width.",height=".$window_height.",left=".$left."'); return false;\" href=\"".$link."\">Image ".$number."</a></div>&nbsp;";
			echo "</td>";
			if(++$number_images > 4) echo "</tr><tr>";
			}
		echo "</tr></table>";
		echo "<br />";
		}
		
	// Process Csound score if possible
	if($file_format == "csound") {
		if($csound_orchestra == '') {
			$csound_orchestra = "default.orc";
			echo "<p><font color=\"red\">➡</font> Csound orchestra file was not specified. I tried the default orchestra: <font color=\"blue\">".$dir.$csound_orchestra."</font>.</p>";
			}
		if(!file_exists($dir.$csound_orchestra)) {
			echo "<p><font color=\"red\">➡</font> No orchestra file has been found here: <font color=\"blue\">".$dir.$csound_orchestra."</font>. Csound will not create a sound file.</p>";
			}
		else {
			$csound_file_link = $output;
			$sound_file_link = str_replace(".sco",".wav",$csound_file_link);
			
			if(file_exists($csound_file_link)) {
				$command = $csound_path."csound --version";
				exec($command,$result_csound,$return_var);
				if($return_var <> 0) {
					echo "<p><font color=\"red\">➡</font> Test of Csound was unsuccessful. May be not installed? The command was: <font color=\"blue\">".$command."</font></p>";
					}
				else {
					@unlink($sound_file_link);
					sleep(1);
					$time_start = time();
					$command = $csound_path."csound --wave -o ".$sound_file_link." ".$dir.$csound_orchestra." ".$csound_file_link;
					echo "<p><small>command = <font color=\"red\">".$command."</font></small></p>";
					exec($command,$result_csound,$return_var);
					if($return_var <> 0) {
						echo "<p><font color=\"red\">➡</font> Csound returned error code <font color=\"red\">‘".$return_var."’</font>.<br /><i>Probably trying to use instruments that do not match</i> <font color=\"blue\">‘".$csound_orchestra."’</font></p>";
						}
					else {
						$time_spent = time() - $time_start;
						if($time_spent > 10)
							echo "<p><font color=\"red\">➡</font> Sorry for the long time (".$time_spent." seconds) waiting for Csound to complete the conversion…</p>";
						$audio_tag = "<audio controls class=\"shadow\">";
						$audio_tag .= "<source src=\"".$sound_file_link."\" type=\"audio/wav\">";
						$audio_tag .= "Your browser does not support the audio tag.";
						$audio_tag .= "</audio>";
						echo $audio_tag;
						echo "<p><a target=\"_blank\" href=\"".$sound_file_link."\" download>Download this sound file</a> (<font color=\"blue\">".$sound_file_link."</font>)</p>";
						echo "<p><font color=\"red\">➡</font> If you hear garbage sound or silence it may be due to mismatch between Csound score and orchestra<br />&nbsp;&nbsp;&nbsp;or some overflow in Csound…</p>";
						}
					}
				}
			else echo "<p><font color=\"red\">➡</font> The score file (".$csound_file_link.") was not found and could not be processed by Csound.</p>";
			}
		}
	}

if($n_messages > 1000) echo "<p>Too many messages produced! (".$n_messages.")</p>";
else {
//	echo "result_file = ".$result_file."<br />";
//	echo $bp_application_path."<br />";
	if($result_file <> '') $handle = fopen($result_file,"w");
	else $handle = FALSE;
	if($handle) {
		$header = "<!DOCTYPE HTML>";
		$header .= "<html lang=\"en\">";
		$header .= "<head>\n";
		$header .= "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />\n";
		$header .= "<link rel=\"stylesheet\" href=\"".$bp_application_path."php/bp.css\" />\n";
		$header .= "<title>".$result_file."</title>\n";
		$header .= "<script type='text/javascript' src='https://www.midijs.net/lib/midi.js'></script>\n";
		$header .= "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js\"></script>\n";
		$header .= "</head><body>\n";
		fwrite($handle,$header."\n");
		fwrite($handle,"<h2 id=\"midi\">".$grammar_name."</h2>\n");
		fwrite($handle,"<small>Results as per ".date('Y-m-d H:i:s')."</small><br />\n");
		if(file_exists($project_name.".wav")) {
			$audio_tag = "<p><b>Csound:</b></p><audio controls class=\"shadow\">";
			$audio_tag .= "<source src=\"".$project_name.".wav\" type=\"audio/wav\">";
			$audio_tag .= "Your browser does not support the audio tag.";
			$audio_tag .= "</audio>";
			fwrite($handle,$audio_tag."<p>\n");
			if(file_exists($project_name.".sco")) {
				fwrite($handle,"<br /><a target=\"_blank\" href=\"".$project_name.".sco\" download>Download Csound score</a> (<font color=\"blue\">".$project_name.".sco</font>)<br />");
				}
			fwrite($handle,"<a target=\"_blank\" href=\"".$project_name.".wav\" download>Download this sound file</a> (<font color=\"blue\">".$project_name.".wav</font>)</p>");
			}
		if(file_exists($project_name.".mid")) {
		//	echo "mid = ".$project_name.".mid<br />";
			$audio_tag = "<p><b>MIDI file:</b></p><p class=\"shadow\" style=\"width:50%;\"><a href=\"#midi\" onClick=\"MIDIjs.play('".$project_name.".mid');\"><img src=\"".$bp_application_path."php/pict/loudspeaker.png\" width=\"70px;\" style=\"vertical-align:middle;\" />Play MIDI file</a>";
			$audio_tag .= " (<a href=\"#midi\" onClick=\"MIDIjs.stop();\">Stop playing</a>)";
			$audio_tag .= "&nbsp;or <a href=\"".$project_name.".mid\" download>download it</a></p>";
			fwrite($handle,$audio_tag."\n");
			}
		if(file_exists($project_name.".html")) {
			fwrite($handle,"<p><font color=\"red\">➡</font> Read the <a onclick=\"window.open('".$project_name.".html','".$file_format."','width=800,height=700,left=300'); return false;\" href=\"".$project_name.".html\">data file</a> (or <a href=\"".$project_name.".bpda\" download>download it</a>)</p>\n");
			}
		fwrite($handle,"<hr><p><b>Messages:</b></p>\n");
		}
	for($i=0; $i < $n_messages; $i++) {
		$mssg = $o[$i];
		$mssg = clean_up_encoding(FALSE,TRUE,$mssg);
		if($handle) fwrite($handle,$mssg."<br />\n");
		if($i < 7) echo $mssg."<br />";
		}
	if($n_messages == 0) echo "No message produced…";
	else {
		echo "<p>…&nbsp;";
		echo "<input style=\"color:DarkBlue; background-color:yellow; font-size:large;\" onclick=\"window.open('".$result_file."','result','width=800,height=600,left=100'); return false;\" type=\"submit\" name=\"produce\" value=\"show all messages…\">";
		echo "</p>";
		}
	if($handle) fwrite($handle,"</body>\n");
	if($handle) fclose($handle);
	}
?>
