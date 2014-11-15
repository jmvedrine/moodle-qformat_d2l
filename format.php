<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * d2l IMSQTI XML question importer.
 *
 * @package    qformat_d2l
 * @copyright  2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/xmlize.php');
require_once($CFG->dirroot . '/question/format/d2l/formatbase.php');
require_once($CFG->dirroot . '/question/format/d2l/formatqti.php');

/**
 * Class to represent a d2l file.
 *
 * @package    qformat_d2l
 * @copyright  2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_d2l_file {
    /** @var int type of file being imported, one of the constants FILETYPE_QTI or FILETYPE_POOL. */
    public $filetype;
    /** @var string the xml text */
    public $text;
    /** @var string path to path to root of image tree in unzipped archive. */
    public $filebase = '';
}

/**
 * d2l QTI file importer class.
 *
 * @package    qformat_d2l
 * @copyright  2014 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_d2l extends qformat_d2l_base {
    /** @var int D2L qti files. */
    const FILETYPE_QTI = 1;

    /**
     * Return the content of a file given by its path in the tempdir directory.
     *
     * @param string $path path to the file inside tempdir
     * @return mixed contents array or false on failure
     */
    public function get_filecontent($path) {
        $fullpath = $this->tempdir . '/' . $path;
        if (is_file($fullpath) && is_readable($fullpath)) {
            return file_get_contents($fullpath);
        }
        return false;
    }

    /**
     * Return content of all files containing questions,
     * as an array one element for each file found,
     * For each file, the corresponding element is an array of lines.
     *
     * @param string $filename name of file
     * @return mixed contents array or false on failure
     */
    public function readdata($filename) {
        global $CFG;

        // We are importing a zip file.
        // Create name for temporary directory.
        $uniquecode = time();
        $this->tempdir = make_temp_directory('d2l_import/' . $uniquecode);
        if (is_readable($filename)) {
            if (!copy($filename, $this->tempdir . '/d2l_questions.zip')) {
                $this->error(get_string('cannotcopybackup', 'question'));
//                fulldelete($this->tempdir);
                return false;
            }
            $packer = get_file_packer('application/zip');
            if ($packer->extract_to_pathname($this->tempdir . '/d2l_questions.zip', $this->tempdir)) {
                $dom = new DomDocument();

                if (!$dom->load($this->tempdir . '/imsmanifest.xml')) {
                    $this->error(get_string('errormanifest', 'qformat_d2l'));
//                    fulldelete($this->tempdir);
                    return false;
                }

                $xpath = new DOMXPath($dom);
                $xpath->registerNamespace('ns', 'http://www.imsglobal.org/xsd/imscp_v1p1');

                // We starts from the root element.
                $query = '//ns:resources/ns:resource';
                $qfile = array();

                $examfiles = $xpath->query($query);
                foreach ($examfiles as $examfile) {
                    $fileobj = new qformat_d2l_file();
                    if ($examfile->getAttribute('identifier') == 'res_question_library') {
                        if ($content = $this->get_filecontent($examfile->getAttribute('href'))) {
                            $fileobj->filetype = self::FILETYPE_QTI;
                            $fileobj->filebase = $this->tempdir;
                            $fileobj->text = $content;
                            $qfile[] = $fileobj;
                        }
                    }
                }

                if ($qfile) {
                    return $qfile;
                } else {
                    $this->error(get_string('cannotfindquestionfile', 'question'));
 //                   fulldelete($this->tempdir);
                }
            } else {
                $this->error(get_string('cannotunzip', 'question'));
//                fulldelete($this->temp_dir);
            }
        } else {
            $this->error(get_string('cannotreaduploadfile', 'error'));
//            fulldelete($this->tempdir);
        }
        return false;
    }

    /**
     * Parse the array of objects into an array of questions.
     * Each object is the content of a .dat questions file.
     * This *could* burn memory - but it won't happen that much
     * so fingers crossed!
     *
     * @param array $lines array of qformat_d2l_file objects for each input file.
     * @return array (of objects) question objects.
     */
    public function readquestions($lines) {

        // Set up array to hold all our questions.
        $questions = array();

        // Each element of $lines is a qformat_d2l_file object.
        foreach ($lines as $fileobj) {
            if ($fileobj->filetype == self::FILETYPE_QTI) {
                $importer = new qformat_d2l_qti();
            } else {
                // In all other cases we are not able to import the file.
                debugging('fileobj type not recognised', DEBUG_DEVELOPER);
                continue;
            }
            $importer->set_filebase($fileobj->filebase);
            $questions = array_merge($questions, $importer->readquestions($fileobj->text));
        }

        // Give any unnamed categories generated names.
        $unnamedcount = 0;
        foreach ($questions as $question) {
            if ($question->qtype == 'category' && $question->category == '') {
                $question->category = get_string('importedcategory', 'qformat_d2l', ++$unnamedcount);
            }
        }

        return $questions;
    }
}
