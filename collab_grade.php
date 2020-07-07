<?php
/**
 * PHP Collab Grading Script
 *
 * This script reads over the unzipped content of a UVA Collab Assignment bulk-download file
 * and checks for student assignment submissions.  It provides two functions that can be
 * modified to check either the submission text box on the Assigment page or the student-
 * uploaded files.  In order for it to process, on the Collab Assignment Grade page, choose
 * "Download All" and check Student submission attachment(s), Grade file (CSV), and Feedback
 * comments. Do not check "Save all selected download options in one folder".
 *
 * Run this script as: php collab_grade.php Unzipped_Folder_Name
 *
 * After running, rezip the folder and choose "Upload All" on the Collab Assignment Grade page.
 * There check Grade file (CSV) and Feedback comments.  Do NOT check Student submission attachment(s).
 *
 * @author Robbie Hott
 */


/**
 * Maximum score, in case the parts add up to more than the max
 */
$maxscore = 10;


$projDir = $argv[1];
$grades = [];

/**
 * Check Submission File
 * 
 * Modify this function if you want to check the submission text of the Collab assignment.
 * Specifically, the HTML text box into which the students write answers.
 * 
 * @param $text string the contents of the Student submission text
 * @return int the score for the submission text
 */
function checkSubmissionFile($text) {
    return 0;
}

/**
 * Check Uploaded File
 * 
 * Modify this function if you want to check the files uploaded by the students.
 * Specifically, this function will be called for each of the uploaded files, given
 * the contents of the file and the name of the file.
 * 
 * @param $file string the contents of the Student-submitted file
 * @param $name string the name of the Student-submitted file
 * @return int the score for the submitted file
 */
function checkUploadedFile($file, $name) {
    return 0;
}


// The code below handles the parsing of the directory structure.  This should not need
// to be modified.
if ($handle = opendir($projDir)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != ".." && is_dir($userdir = $projDir ."/".$entry)) {
            preg_match("/(.*)\(([a-z]+[0-9][a-z]+)\)/", $entry, $matches);
            $userid = $matches[2];
            $name = $matches[1];
            $score = 0;
            $comments = "";
            echo "$entry\n";
            echo "Userid: $userid, Name: $name\n";

            if (is_file("$userdir/{$entry}_submissionText.html")) {
                $submission = file_get_contents("$userdir/{$entry}_submissionText.html");
                if(($score += checkSubmissionFile($submission)) > 0)
                    $comments .= "    Submission text found.\n";
            }

            $submitDir = "$userdir/Submission attachment(s)/";
            if ($h2 = opendir($submitDir)) {
                while (false !== ($fn = readdir($h2))) {
                    if ($fn != "." && $fn != "..") {
                        $comments .= "    Submitted file: $fn\n";
                        $submission = file_get_contents("$submitDir$fn");
                        if (($score += checkUploadedFile($submission, $fn)) > 0) {
                            $comments .= "      non-empty\n";
                        }
                    }
                }
            }
            
            if ($score > $maxscore)
                $score = $maxscore;

            $comments .= "  Score: $score\n\n";
            echo $comments;
            file_put_contents("$userdir/comments.txt", $comments); 

            $grades[$userid] = [
                "grade" => $score,
                "comments" => $comments
            ];

        }
    }
    closedir($handle);
    
    // Read original grades file
    $gradesCSV = [];
    $fp = fopen($projDir.'/grades.csv', 'r');
    while (($data = fgetcsv($fp, 1000, ",")) !== FALSE) {
        array_push($gradesCSV, $data);
    }
    fclose($fp); 

    // Write the updated version
    $fp = fopen($projDir.'/grades.csv', 'w');
    $i = 0;
    foreach ($gradesCSV as $row) {
        if ($i++ > 2) {
            if (isset($grades[$row[1]]) && $grades[$row[1]]["grade"] != 0) {
                $row[4] = $grades[$row[1]]["grade"];
            }
        }
        fputcsv($fp, $row);
    }
    fclose($fp); 
}

