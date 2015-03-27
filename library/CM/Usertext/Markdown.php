<?php

class CM_Usertext_Markdown extends Michelf\MarkdownExtra {

    /** @var bool $_skipAnchors */
    private $_skipAnchors;

    /** @var bool $_imgLazy */
    private $_imgLazy;

    /**
     * @param bool|null $skipAnchors
     * @param bool|null $imgLazy
     */
    function __construct($skipAnchors = null, $imgLazy = null) {
        $this->_skipAnchors = (boolean) $skipAnchors;
        $this->_imgLazy = (boolean) $imgLazy;
        parent::__construct();
    }

    function formParagraphs($text) {
        $text = preg_replace('/\A\n+|\n+\z/', '', $text);
        $grafs = preg_split('/\n{1,}/', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($grafs as $key => $value) {
            if (!preg_match('/^B\x1A[0-9]+B$/', $value)) {
                # Is a paragraph.
                $value = $this->runSpanGamut($value);
                $value = preg_replace('/^([ ]*)/', "<p>", $value);
                $value .= "</p>";
                $grafs[$key] = $this->unhash($value);
            } else {
                # Is a block.
                # Modify elements of @grafs in-place...
                $graf = $value;
                $block = $this->html_hashes[$graf];
                $graf = $block;
                $grafs[$key] = $graf;
            }
        }
        return implode("\n", $grafs);
    }

    function _doAnchors_inline_callback($matches) {
        if (!$this->_skipAnchors) {
            return parent::_doAnchors_inline_callback($matches);
        }
        $link_text = $this->runSpanGamut($matches[2]);
        return $this->hashPart($link_text);
    }

    function _doAnchors_reference_callback($matches) {
        if (!$this->_skipAnchors) {
            return parent::_doAnchors_inline_callback($matches);
        }
        $link_text = $matches[2];
        return $link_text;
    }

    protected function _doImages_reference_callback($matches) {
        if (!$this->_imgLazy) {
            return parent::_doImages_reference_callback($matches);
        }
        $key = parent::_doImages_reference_callback($matches);
        $this->html_hashes[$key] = $this->_addLazyAttrs($this->html_hashes[$key]);
        return $key;
    }

    protected function _doImages_inline_callback($matches) {
        if (!$this->_imgLazy) {
            return parent::_doImages_inline_callback($matches);
        }
        $key = parent::_doImages_inline_callback($matches);
        $this->html_hashes[$key] = $this->_addLazyAttrs($this->html_hashes[$key]);
        return $key;
    }

    /**
     * @param string $tagText
     * @return string
     */
    private function _addLazyAttrs($tagText) {
        $tagText = str_replace('>', ' class="lazy">', $tagText);
        return str_replace('src=', 'data-original=', $tagText);
    }
}
