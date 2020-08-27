<?php
namespace MDword;

use MDword\Edit\Part\Document;
use MDword\Read\Word;
use MDword\Edit\Part\Comments;
use MDword\Common\Bind;
use MDword\Edit\Part\Header;
use MDword\Edit\Part\Footer;

class WordProcessor
{
    private $wordsIndex = -1;
    private $words = [];
    public $isForTrace = false;
    
    public function __construct() {
        require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'Config'.DIRECTORY_SEPARATOR.'Main.php');
    }
    
    public function load($zip) {
        $reader = new Word();
        $reader->wordProcessor = $this;
        $reader->load($zip);
        $this->words[++$this->wordsIndex] = $reader;
        
        $this->words[$this->wordsIndex]->commentsEdit = [];
        foreach ($this->words[$this->wordsIndex]->parts[15] as $part15) {
            $comments = $part15['DOMElement'];
            $Comment = new Comments($this->words[$this->wordsIndex],$comments);
            $Comment->partName = $part15['PartName'];
            $Comment->word = $this->words[$this->wordsIndex];
            $this->words[$this->wordsIndex]->commentsEdit[] = $Comment;
        }
        
        return $this->words[$this->wordsIndex];
    }
    
    /**
     * @return \MDword\Common\Bind
     */
    public function getBind($data) {
        $bind = new Bind($this,$data);
        return $bind;
    }
    
    public function setValue($name, $value, $type=MDWORD_TEXT) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($name, $value, $type);
        }
    }
    
    public function setValues($values,$pre='') {
        foreach ($values as $index => $valueArr) {
            foreach($valueArr as $name => $value) {
                if(is_array($value)) {
                    $this->setValues($value,'#'.$index);
                }else{
                    $this->setValue($name.$pre.'#'.$index, $value);
                }
            }
        }
    }
    
    /**
     * delete p include the block 
     * @param string $name
     */
    public function deleteP(string $name) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($name, 'p',MDWORD_DELETE);
        }
    }
    
    /**
     * delete tr include the block
     * @param string $name
     */
    public function deleteTr(string $name) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($name, 'tr',MDWORD_DELETE);
        }
    }
    
    /**
     * delete block
     * @param string $name
     */
    public function delete(string $name) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($name, '',MDWORD_TEXT);
        }
    }
    
    public function setImageValue($name, $value) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($name, $value,MDWORD_IMG);
        }
    }
    
    /**
     * @param string $name
     * ['text','link']
     * @param array $value
     */
    public function setLinkValue($name, $value) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($name, $value[0],MDWORD_TEXT);
            $documentEdit->setValue($name, $value[1],MDWORD_LINK);
        }
    }
    
//     /**
//      * @param string $name
//      * @param array $datas
//      * change value ['A1',9,'set']
//      * extention range ['$A$1:$A$5','$A$1:$A$10','ext']
//      */
//     public function setExcelValues($name='',$datas=[]) {
//         $documentEdit = $this->getDocumentEdit();
//         $documentEdit->setValue($name, $datas, 'excel');
//     }
    
    /**
     * clone p
     * @param string $name
     * @param int $count
     */
    public function cloneP($name,$count=1) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($name, $count, MDWORD_CLONEP);
        }
    }
    /**
     * clone
     * @param string $name
     * @param int $count
     */
    public function clones($name,$count=1) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($name, $count, MDWORD_CLONE);
        }
    }
    /**
     * clone
     * @param string $name
     * @param int $count
     */
    public function cloneTo($nameTo,$name) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($nameTo, $name, MDWORD_CLONETO);
        }
    }
    
    
    public function setBreakValue($name, $value) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($name, $value,MDWORD_BREAK);
        }
    }
    
    
    public function setBreakPageValue($name, $value=1) {
        foreach($this->words[$this->wordsIndex]->needUpdateParts as $func) {
            /**
             * @var Document $documentEdit
             */
            $documentEdit = $this->$func();
            $documentEdit->setValue($name, $value,MDWORD_PAGE_BREAK);
        }
    }
    
    /**
     * update toc
     */
    public function updateToc() {
        $documentEdit = $this->getDocumentEdit();
        $documentEdit->updateToc();
    }
    
    private function getHeaderEdit() {
        $headerEdit = $this->words[$this->wordsIndex]->headerEdit;
        if(is_null($headerEdit)) {
            $document = $this->words[$this->wordsIndex]->parts[22][0]['DOMElement'];
            $blocks = [];
            foreach($this->words[$this->wordsIndex]->commentsEdit as $coments) {
                if($coments->partName === 'word/comments.xml') {
                    $blocks = array_merge($this->words[$this->wordsIndex]->blocks,$coments->blocks);
                }
            }
            $headerEdit = new Header($this->words[$this->wordsIndex],$document,$blocks);
            $this->words[$this->wordsIndex]->headerEdit = $headerEdit;
            $this->words[$this->wordsIndex]->headerEdit->partName = $this->words[$this->wordsIndex]->parts[2][0]['PartName'];
        }
        return $headerEdit;
    }
    
    private function getDocumentEdit() {
        $documentEdit = $this->words[$this->wordsIndex]->documentEdit;
        if(is_null($documentEdit)) {
            $document = $this->words[$this->wordsIndex]->parts[2][0]['DOMElement'];
            $blocks = [];
            foreach($this->words[$this->wordsIndex]->commentsEdit as $coments) {
                if($coments->partName === 'word/comments.xml') {
                    $blocks = array_merge($this->words[$this->wordsIndex]->blocks,$coments->blocks);
                }
            }
            $documentEdit = new Document($this->words[$this->wordsIndex],$document,$blocks);
            $this->words[$this->wordsIndex]->documentEdit = $documentEdit;
            $this->words[$this->wordsIndex]->documentEdit->partName = $this->words[$this->wordsIndex]->parts[2][0]['PartName'];
        }
        return $documentEdit;
    }
    
    private function getFooterEdit() {
        $footerEdit = $this->words[$this->wordsIndex]->footerEdit;
        if(is_null($footerEdit)) {
            $document = $this->words[$this->wordsIndex]->parts[23][0]['DOMElement'];
            $blocks = [];
            foreach($this->words[$this->wordsIndex]->commentsEdit as $coments) {
                if($coments->partName === 'word/comments.xml') {
                    $blocks = array_merge($this->words[$this->wordsIndex]->blocks,$coments->blocks);
                }
            }
            $footerEdit = new Footer($this->words[$this->wordsIndex],$document,$blocks);
            $this->words[$this->wordsIndex]->footerEdit = $footerEdit;
            $this->words[$this->wordsIndex]->footerEdit->partName = $this->words[$this->wordsIndex]->parts[2][0]['PartName'];
        }
        return $footerEdit;
    }
    
    public function getStylesEdit() {
        $stylesEdit = $this->words[$this->wordsIndex]->stylesEdit;
        if(is_null($stylesEdit)) {
            $document = $this->words[$this->wordsIndex]->parts[4][0]['DOMElement'];
            $stylesEdit = new Document($this->words[$this->wordsIndex],$document);
            $this->words[$this->wordsIndex]->stylesEdit = $stylesEdit;
            $this->words[$this->wordsIndex]->stylesEdit->partName = $this->words[$this->wordsIndex]->parts[4][0]['PartName'];
        }
        
        return $stylesEdit;
    }
    
    public function saveAs($fileName)
    {
        $tempFileName = $this->words[$this->wordsIndex]->save();
        
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        
        copy($tempFileName, $fileName);
        unlink($tempFileName);
    }
    
    public function saveAsToPathForTrace($dir,$baseName)
    {
        static $idx = 0;
        $word = $this->words[$this->wordsIndex];
        $tempFileName = $word->saveForTrace();
        
        $fileName = $dir.'/'.$baseName.'-'.str_pad($idx++,3,"0",STR_PAD_LEFT).'.docx';
        
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        copy($tempFileName, $fileName);
        
        $WordProcessor = new WordProcessor();
        $WordProcessor->isForTrace = true;
        $WordProcessor->load($fileName);
        $WordProcessor->saveAs($fileName);
    }
    
    public function setChartValue($name='',$fileName)
    {
        $reader = new Word();
        $reader->load($fileName);
        $this->words[++$this->wordsIndex] = $reader;
        $documentEdit = $this->getDocumentEdit();

        $documentChart = $documentEdit->getDocumentChart();

        $chartparts = $this->words[$this->wordsIndex]->getChartParts();
        $embeddings = $this->words[$this->wordsIndex]->getChartEmbeddings();

        $this->words[--$this->wordsIndex]->setChartParts($chartparts);
        $documentEdit = $this->getDocumentEdit();
        $documentEdit->setDocumentChart($name,$documentChart);
        $this->words[$this->wordsIndex]->updateChartRel();
        $this->words[$this->wordsIndex]->setContentTypes();
        $this->setEmbeddings();
   
    }

    public function setEmbeddings(){
        $this->words[$this->wordsIndex]->parts[13];
        foreach($this->words[$this->wordsIndex]->parts[13] as $part){
            if(!empty($part['embeddings'])){
                preg_match('/(\d+)/',$part['PartName'],$match);
                $fileName = preg_replace('/(\d+)/',$match[1],$part['embeddings']['name']);
                $this->words[$this->wordsIndex]->zip->addFromString('word/embeddings/'.$fileName, $part['embeddings']['xml']);
            }
        }
    }
    
    public function showMedies() {
        $word = $this->words[$this->wordsIndex];
        $numFiles = $word->zip->numFiles;
        $showList = [];
        for ($i = 0; $i < $numFiles; $i++) {
            $name = $word->zip->getNameIndex($i);
            if(strpos($name, 'media') > 0) {
                $content = $word->zip->getFromIndex($i);
                $showList['medias'][] = [
                    'md5' => md5($content),
                    'name' => $word->zip->getNameIndex($i),
                    'content' => $content,
                ];
            }
        }
        
        foreach($showList as $medias) {
            foreach($medias as $media) {
                var_dump($media);
                echo '<img src="data:image/jpeg;base64,'.base64_encode($media['content']).'"/><br/>';
            }
            
        }
    }
}
