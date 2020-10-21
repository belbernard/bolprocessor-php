<?php
require_once("_basic_tasks.php");

$autosave = TRUE;
// $autosave = FALSE;

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "objects.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);

if(isset($_POST['createcsoundinstruments'])) {
	$CsoundInstruments_filename = $_POST['CsoundInstruments_filename'];
	$handle = fopen($dir.$CsoundInstruments_filename,"w");
	$template = "csound_template";
	$template_content = @file_get_contents($template,TRUE);
	fwrite($handle,$template_content."\n");
	fclose($handle);
	$path = str_replace($bp_application_path,'',$dir);
	$url = "csound.php?file=".urlencode($path.$CsoundInstruments_filename);
	header("Location: ".$url); 
	}

require_once("_header.php");
echo "<p>Current directory = ".$dir;
echo "   <span id='message1' style=\"margin-bottom:1em;\"></span>";
echo "</p>";
echo link_to_help();

echo "<h2>Object prototypes file “".$filename."”</h2>";

if($test) echo "dir = ".$dir."<br />";

$temp_folder = str_replace(' ','_',$filename)."_".session_id()."_temp";
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}

if(isset($_POST['create_object'])) {
	$new_object = trim($_POST['new_object']);
	$new_object = str_replace(' ','-',$new_object);
	$new_object = str_replace('"','',$new_object);
	if($new_object <> '') {
		$template = "object_template";
		$template_content = @file_get_contents($template,TRUE);
		$new_object_file = $temp_dir.$temp_folder.SLASH.$new_object.".txt";
		$handle = fopen($new_object_file,"w");
		$file_header = $top_header."\n// Object prototype saved as \"".$new_object."\". Date: ".gmdate('Y-m-d H:i:s');
		fwrite($handle,$file_header."\n");
		fwrite($handle,$filename."\n");
		fwrite($handle,$template_content."\n");
		fclose($handle);
		}
	}

if(isset($_POST['duplicate_object'])) {
	$object = $_POST['object_name'];
	$copy_object = trim($_POST['copy_object']);
	$copy_object = str_replace(' ','-',$copy_object);
	$copy_object = str_replace('"','',$copy_object);
	$this_object_file = $temp_dir.$temp_folder.SLASH.$object.".txt";
	$copy_object_file = $temp_dir.$temp_folder.SLASH.$copy_object.".txt";
//	$copy_object_file_deleted = $temp_dir.$temp_folder.SLASH.$copy_object.".txt.old";
//	if(!file_exists($copy_object_file) AND !file_exists($copy_object_file_deleted)) {
	if(!file_exists($copy_object_file)) {
		copy($this_object_file,$copy_object_file);
		@unlink($temp_dir.$temp_folder.SLASH.$copy_object.".txt.old");
		$this_object_codes = $temp_dir.$temp_folder.SLASH.$object."_codes";
		$copy_object_codes = $temp_dir.$temp_folder.SLASH.$copy_object."_codes";
		rcopy($this_object_codes,$copy_object_codes);
		}
	else echo "<p><font color=\"red\">Cannot create</font> <font color=\"blue\"><big>“".$copy_object."”</big></font> <font color=\"red\">because an object with that name already exists</font></p>";
	}

if(isset($_POST['delete_object'])) {
	$object = $_POST['object_name'];
	echo "<p><font color=\"red\">Deleted </font><font color=\"blue\"><big>“".$object."”</big></font>…</p>";
	$this_object_file = $temp_dir.$temp_folder.SLASH.$object.".txt";
//	echo $this_object_file."<br />";
	rename($this_object_file,$this_object_file.".old");
	}

if(isset($_POST['restore'])) {
	echo "<p><font color=\"red\">Restoring: </font>";
	// echo "<font color=\"blue\">".$object."</font> </p>";
	$dircontent = scandir($temp_dir.$temp_folder);
	foreach($dircontent as $oldfile) {
		if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
		$table = explode(".",$oldfile);
		$extension = end($table);
		if($extension <> "old") continue;
		$thisfile = str_replace(".old",'',$oldfile);
		echo "<font color=\"blue\">".str_replace(".txt",'',$thisfile)."</font> ";
		$this_object_file = $temp_dir.$temp_folder.SLASH.$oldfile;
		rename($this_object_file,str_replace(".old",'',$this_object_file));
		}
	echo "</p>";
	}

$deleted_objects = '';
$dircontent = scandir($temp_dir.$temp_folder);
foreach($dircontent as $oldfile) {
	if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
	$table = explode(".",$oldfile);
	$extension = end($table);
	if($extension <> "old") continue;
	$thisfile = str_replace(".old",'',$oldfile);
	$this_object = str_replace(".txt",'',$thisfile);
	$deleted_objects .= "“".$this_object."” ";
	}

if(isset($_POST['savethisfile']) OR isset($_POST['create_object']) OR isset($_POST['delete_object']) OR isset($_POST['restore']) OR isset($_POST['duplicate_object'])) {
	if($test) echo "SaveObjectPrototypes() dir = ".$dir."<br />";
	if($test) echo "filename = ".$filename."<br />";
	if($test) echo "temp_folder = ".$temp_folder."<br />";
	echo "<p id=\"timespan\"><font color=\"red\">Saved file:</font> <font color=\"blue\">";
	SaveObjectPrototypes(TRUE,$dir,$filename,$temp_dir.$temp_folder);
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$objects_file = $csound_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$extract_data = extract_data(TRUE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$csound_file = $extract_data['csound'];

$comment_on_file = '';
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"temp_dir\" value=\"".$temp_dir."\">";
echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";
// echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
$table = explode(chr(10),$content);
$iobj = -1;
$handle_object = FALSE;
for($i = 0; $i < count($table); $i++) {
	$line = $table[$i];
	if($i == 0) {
		$PrototypeTickKey = $line;
		echo "PrototypeTickKey = <input type=\"text\" name=\"PrototypeTickKey\" size=\"4\" value=\"".$PrototypeTickKey."\"><br />";
		}
	if($i == 1) {
		$PrototypeTickChannel = $line;
		echo "PrototypeTickChannel = <input type=\"text\" name=\"PrototypeTickChannel\" size=\"4\" value=\"".$PrototypeTickChannel."\"><br />";
		}
	if($i == 2) {
		$PrototypeTickVelocity = $line;
		echo "PrototypeTickVelocity = <input type=\"text\" name=\"PrototypeTickVelocity\" size=\"4\" value=\"".$PrototypeTickVelocity."\"><br />";
		}
	if($i == 3) {
		$CsoundInstruments_filename = trim($line);
		if($CsoundInstruments_filename <> '' AND !is_integer(strpos($CsoundInstruments_filename,"-cs.")) AND !is_integer(strpos($CsoundInstruments_filename,".bpcs")))
			$CsoundInstruments_filename .= ".bpcs";
		echo "<input type=\"hidden\" name=\"CsoundInstruments_filename\" value=\"".$CsoundInstruments_filename."\">";
		echo "CsoundInstruments filename = <input type=\"text\" name=\"CsoundInstruments_filename\" size=\"20\" value=\"".$CsoundInstruments_filename."\">";
		if($CsoundInstruments_filename <> '') { 
			echo "&nbsp;➡&nbsp;";
			$CsoundInstruments_file = $dir.$CsoundInstruments_filename;
			$path = str_replace($bp_application_path,'',$dir);
			if($CsoundInstruments_filename <> '' AND file_exists($CsoundInstruments_file)) {
				echo "<a target=\"_blank\" href=\"csound.php?file=".urlencode($path.$CsoundInstruments_filename)."\">edit this file</a>";
				}
			else {
				echo "File not found: <input style=\"background-color:yellow;\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" name=\"createcsoundinstruments\" value=\"CREATE ‘".$CsoundInstruments_filename."’\">";
				}
			}
		else $CsoundInstruments_file = '';
		echo "<br />";
		}
	if($i == 4) {
		$maxsounds = $line;
		echo "<input type=\"hidden\" name=\"maxsounds\" value=\"".$maxsounds."\">";
		}
	if($line == "TABLE:") break;
	if($line == "DATA:") {
		$comment_on_file = $table[$i+1];
		$comment_on_file = str_ireplace("<HTML>",'',$comment_on_file);
		$comment_on_file = str_ireplace("</HTML>",'',$comment_on_file);
		echo "Comment on this file = <input type=\"text\" name=\"comment_on_file\" size=\"80\" value=\"".$comment_on_file."\"><br />";
		break;
		}
	if(!is_integer($pos=stripos($line,"<HTML>"))) continue;
	else {
		$iobj++;
		$clean_line = str_ireplace("<HTML>",'',$line);
		$clean_line = str_ireplace("</HTML>",'',$clean_line);
		$object_name[$iobj] = trim($clean_line);
		
		$object_da[$iobj] = $temp_dir.$temp_folder.SLASH.$object_name[$iobj].".bpda";
		$handle_da = fopen($object_da[$iobj],"w");
		$file_header = $top_header."\n// Data saved as \"".$object_name[$iobj].".bpda\". Date: ".gmdate('Y-m-d H:i:s');
		fwrite($handle_da,$file_header."\n");
		fwrite($handle_da,$object_name[$iobj]."\n");
		fclose($handle_da);
		$object_file[$iobj] = $temp_dir.$temp_folder.SLASH.$object_name[$iobj].".txt";
		$object_foldername = clean_folder_name($object_name[$iobj]);
		$save_codes_dir = $temp_dir.$temp_folder.SLASH.$object_foldername."_codes";
		if(!is_dir($save_codes_dir)) mkdir($save_codes_dir);
		if($handle_object) fclose($handle_object);
		$handle_object = fopen($object_file[$iobj],"w");
		$midi_bytes = $save_codes_dir."/midibytes.txt";
		$handle_bytes = fopen($midi_bytes,"w");
		
		$csound_file_this_object = $save_codes_dir."/csound.txt";
		$handle_csound = fopen($csound_file_this_object,"w");
		
		$file_header = $top_header."\n// Data saved as \"".$object_name[$iobj]."\". Date: ".gmdate('Y-m-d H:i:s');
		$file_header .= "\n".$filename;
		fwrite($handle_object,$file_header."\n");
		echo "<input type=\"hidden\" name=\"object_name_".$iobj."\" value=\"".$object_name[$iobj]."\">";
		$j = $i_start_midi = $n = 0; $first = TRUE;
		$has_csound[$iobj] = FALSE;
		do {
			$i++; $line = $table[$i];
			if(is_integer($pos=strpos($line,"_beginCsoundScore_"))) {
				$i++; $line = $table[$i];
			/*	if(is_integer($pos=strpos($line,"_endCsoundScore_"))) {
					$score = "<HTML></HTML>";
					}
				else */
				while(!is_integer($pos=strpos($line,"_endCsoundScore_"))) {
					$test_csound = preg_replace("/i[0-9]\s/u","•§§§•",$line);
					if(is_integer($pos=strpos($test_csound,"•§§§•")))
						$has_csound[$iobj] = TRUE;
				//	echo $test_csound."<br />";
					$score_line = str_ireplace("<HTML>",'',$line);
					$score_line = str_ireplace("</HTML>",'',$score_line);
					$score_line = str_ireplace("<BR>","\n",$score_line);
					$score_line = str_ireplace("_beginCsoundScore_","\n",$score_line);
					fwrite($handle_csound,$score_line."\n");
					$i++; $line = $table[$i];
					}
				$i_start_midi = $i;
				}
			// We send MIDI codes to separate file"midibytes.txt"
			$number_codes = FALSE;
			if($i_start_midi > 0 AND $i > $i_start_midi AND !is_integer(stripos($line,"<HTML>"))) {
				if($first) {
					$nmax = intval($line);
					$first = FALSE;
				//	echo $object_name[$iobj]." nmax = ".$nmax."<br />";
					$number_codes = TRUE;
					}
				if($n <= $nmax) fwrite($handle_bytes,$line."\n");
				$n++;
				}
			else if(!$number_codes AND !is_integer(strpos($line,"_endCsoundScore_")))
				fwrite($handle_object,$line."\n");
			if(is_integer($pos=stripos($line,"<HTML>"))) break;
			$j++;
			continue;
			}
		while(TRUE);
		$clean_line = str_ireplace("<HTML>",'',$line);
		$clean_line = str_ireplace("</HTML>",'',$clean_line);
		$object_comment[$iobj] = $clean_line;
		fclose($handle_bytes);
		fclose($handle_csound);
		}
	}
		
if($handle_object) fclose($handle_object);
echo "<p style=\"color:blue;\">".$comment_on_file."</p>";
echo "<p style=\"text-align:left;\">";
echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’ INCLUDING ALL CHANGES TO PROTOTYPES\"><br />";
echo "➡ <i>This file is autosaved every 30 seconds. Still, it is safe to save it before quitting the editor.</i></p>";
if($autosave) echo "<script type=\"text/javascript\" src=\"autosaveObjects.js\"></script>";

echo "<p><input style=\"background-color:yellow;\" type=\"submit\" name=\"create_object\" value=\"CREATE A NEW OBJECT\"> named <input type=\"text\" name=\"new_object\" size=\"10\" value=\"\"></p>";
if($deleted_objects <> '') echo "<p><input style=\"background-color:yellow;\" type=\"submit\" name=\"restore\" value=\"RESTORE ALL DELETED OBJECTS\"> = <font color=\"blue\"><big>".$deleted_objects."</big></font></p>";

echo "</form>";

echo "<hr>";
echo "<h3>Click object prototypes below to edit them:</h3>";

$temp_alphabet_file = $temp_dir.$temp_folder.SLASH."temp.bpho";
$handle = fopen($temp_alphabet_file,"w");
$file_header = $top_header."\n// Alphabet saved as \"temp.bpho\". Date: ".gmdate('Y-m-d H:i:s');
fwrite($handle,$file_header."\n");
fwrite($handle,$filename."\n");
fwrite($handle,"*\n");
echo "<table style=\"background-color:lightgrey;\">";
for($i = 0; $i <= $iobj; $i++) {
	echo "<tr><td style=\"padding:4px; vertical-align:middle;\">";
	echo "<form method=\"post\" action=\"prototype.php\" enctype=\"multipart/form-data\">";
//	echo "<input type=\"hidden\" name=\"temp_dir\" value=\"".$temp_dir."\">";
	echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
	echo "<input type=\"hidden\" name=\"object_file\" value=\"".$object_file[$i]."\">";
	echo "<input type=\"hidden\" name=\"prototypes_file\" value=\"".$dir.$filename."\">";
	echo "<input type=\"hidden\" name=\"prototypes_name\" value=\"".$filename."\">";
	echo "<input type=\"hidden\" name=\"CsoundInstruments_file\" value=\"".$CsoundInstruments_file."\">";
	echo "<input style=\"background-color:azure; font-size:larger;\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" name=\"object_name\" value=\"".$object_name[$i]."\">";
	fwrite($handle,$object_name[$i]."\n");
	echo "</form>";
	echo "</td>";
	echo "<td style=\"vertical-align:middle;\">";
	echo $object_comment[$i];
	echo "</td>";
	echo "<td style=\"vertical-align:middle;\">";
	if($has_csound[$i]) echo "Csound";
	echo "</td>";
	echo "<td style=\"padding:4px; vertical-align:middle;\">";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"dir\" value=\"".$dir."\">";
	echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";
//	echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
	echo "<input type=\"hidden\" name=\"PrototypeTickKey\" value=\"".$PrototypeTickKey."\">";
	echo "<input type=\"hidden\" name=\"PrototypeTickChannel\" value=\"".$PrototypeTickChannel."\">";
	echo "<input type=\"hidden\" name=\"PrototypeTickVelocity\" value=\"".$PrototypeTickVelocity."\">";
	echo "<input type=\"hidden\" name=\"CsoundInstruments_filename\" value=\"".$CsoundInstruments_filename."\">";
	echo "<input type=\"hidden\" name=\"comment_on_file\" value=\"".$comment_on_file."\">";
	echo "<input type=\"hidden\" name=\"maxsounds\" value=\"".$maxsounds."\">";
	echo "<input type=\"hidden\" name=\"object_name\" value=\"".$object_name[$i]."\">";
	echo "<input style=\"background-color:yellow; \" type=\"submit\" name=\"delete_object\" value=\"DELETE\">";
	echo "</td>";
	echo "<td style=\"padding:4px; vertical-align:middle; text-align:right;\">";
	echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"duplicate_object\" value=\"DUPLICATE AS\">: <input type=\"text\" name=\"copy_object\" size=\"15\" value=\"\">";
	echo "</td>";
	echo "</tr>";
	echo "</form>";
	}
echo "</table>";
fclose($handle);

display_more_buttons($content,$url_this_page,$dir,$objects_file,$csound_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);

?>
