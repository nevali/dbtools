<?php

/*
 * Copyright 2011 Mo McRoberts.
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

abstract class Docbook
{
	public static $blocks = array('para', 'simplepara', 'note', 'tip', 'warning');
}

class DocbookProcessor
{
	protected $doc;
	protected $root;

	public function __construct($filename)
	{
		$this->doc = new DOMDocument();
		$this->doc->load($filename);
		$this->root = $this->doc->documentElement;
	}

	public function process($output)
	{
		switch($this->root->localName)
		{
		case 'book':
			$p = new DocbookBookProcessor($this->root);
			$p->process($output);
			break;
		default:
			trigger_error("Don't know how to process a <" . $this->root->localName . ">", E_USER_ERROR);
			return;
		}
	}
}

class DocbookBookProcessor
{
	protected $book;

	public $title;
	public $author;
	public $company;
	public $copyright;
	public $comments;
	public $keywords;
	
	public function __construct($book)
	{
		$this->book = $book;
		$this->scan();
	}

	protected function scan()
	{
		for($child = $this->book->firstChild; $child; $child = $child->nextSibling)
		{
			if($child instanceof DOMElement)
			{
				if($child->localName == 'info')
				{
					$this->scanInfo($child);
				}
				else if($child->localName == 'title')
				{
					$this->title = new DocbookInlineProcessor($child);
				}

			}
		}
	}
	
	protected function scanInfo($info)
	{
		for($child = $info->firstChild; $child; $child = $child->nextSibling)
		{
			if($child instanceof DOMElement)
			{
				if($child->localName == 'title')
				{
					$this->title = new DocbookInlineProcessor($child);
				}
			}
		}
	}

	public function process($output)
	{
		$output->title = $this->title->textContent;
		$output->company = $this->company;
		$output->author = $this->author;
		$this->coverPage($output);
		$tocSect = $output->section();
		$toc = array();
		for($child = $this->book->firstChild; $child; $child = $child->nextSibling)
		{
			if($child instanceof DOMElement)
			{
				if($child->localName == 'info')
				{
					continue;
				}
				if($child->localName == 'section')
				{
					$sect = new DocbookSectionProcessor($child);
					$sect->process($output);
					$toc[] = $sect;
					continue;
				}
				if($child->localName == 'chapter')
				{
					$sect = new DocbookChapterProcessor($child);
					$sect->process($output);
					$toc[] = $sect;
					continue;
				}
				trigger_error("Don't know how to process a <" . $child->getName() . ">", E_USER_ERROR);
			}

		}		
		if(count($toc))
		{
			$tocSect->break = 'page';
			$tocSect->para($output->style('Heading 1'))->span('Contents');
			$level = 1;
			$this->emitToc($output, $tocSect, $toc);
		}
		else
		{
			$tocSect->hide = true;
		}
	}	

	protected function emitToc($output, $tocSect, $toc, $level = 1)
	{
		foreach($toc as $entry)
		{
			if($entry->title !== null)
			{
				$p = $tocSect->para($output->style('TOC Level ' . $level));
				$entry->title->process($output, $tocSect, $p, null, null);
				$this->emitToc($output, $tocSect, $entry->toc, $level + 1);
			}
		}
	}

	protected function processInfo($info)
	{
		if(isset($info->title))
		{
			$this->title = $info->title;
		}
		if(isset($info->author))
		{
			$n = '';
			if(isset($info->author->honorific))
			{
				$n .= $info->author->honorific . ' ';
			}
			if(isset($info->author->firstname))
			{
				$n .= $info->author->firstname . ' ';
			}
			if(isset($info->author->surname))
			{
				$n .= $info->author->surname . ' ';
			}
			if(strlen($n))
			{
				$this->author = trim($n);
			}
			if(isset($info->author->affiliation))
			{
				if(isset($info->author->affiliation->orgname))
				{
					$this->company = $info->author->affiliation->orgname;
				}
			}
		}
	}

	protected function coverPage($rtf)
	{
		$cover = $rtf->section();
		$cover->verticalAlign = 'c';

		if($this->title !== null)
		{
			$p = $cover->para($rtf->style('Title'));
			$p->style->justify = 'center';
			$this->title->process($rtf, $cover, $p, null, null);
		}
		$p = $cover->para($rtf->style('Cover-page author'));
		$p->style->justify = 'center';
		$p->span($this->author);

		$p = $cover->para($rtf->style('Cover-page affiliation'));
		$p->style->justify = 'center';
		$p->span($this->company);
	}
}

class DocbookSectionProcessor
{
	protected $section;
	protected $depth;
	public $toc;
	public $title;

	public function __construct($sect, $depth = 1)
	{
		$this->section = $sect;
		$this->depth = $depth;
		$this->toc = array();
		$this->scan();
	}

	protected function section($rtf, $sect)
	{
		if($sect !== null)
		{
			return $sect;
		}		
		$sect = $rtf->section();
		$sect->break = 'none';
		return $sect;
	}

	public function process($rtf, $sect = null)
	{
		$sect = $this->section($rtf, $sect);
		$this->processHeader($rtf, $sect);
		$this->processChildren($rtf, $sect);
	}

	protected function scan()
	{
		for($child = $this->section->firstChild; $child; $child = $child->nextSibling)
		{
			if($child instanceof DOMElement)
			{
				if($child->localName == 'info')
				{
					$this->scanInfo($child);
				}
				else if($child->localName == 'title')
				{
					$this->title = new DocbookInlineProcessor($child);
				}
			}
		}
	}
	
	protected function scanInfo($info)
	{
		for($child = $info->firstChild; $child; $child = $child->nextSibling)
		{
			if($child instanceof DOMElement)
			{
				if($child->localName == 'title')
				{
					$this->title = new DocbookInlineProcessor($child);
				}
			}
		}
	}

	protected function processHeader($rtf, $sect)
	{
		if(isset($this->title))
		{
			$para = $sect->para($rtf->style('Heading ' . $this->depth));
			$this->title->process($rtf, $sect, $para, null, null);
		}
	}

	protected function processChildren($rtf, $sect)
	{
		for($child = $this->section->firstChild; $child; $child = $child->nextSibling)
		{
			if($child instanceof DOMElement)
			{
				if($child->localName == 'title' || $child->localName == 'info')
				{
					continue;
				}
				if($child->localName == 'chapter')
				{
					$p = new DocbookChapterProcessor($child, $this->depth + 1);
					$p->process($rtf, $sect);
					$this->toc[] = $p;
					continue;
				}
				if($child->localName == 'section')
				{
					$p = new DocbookSectionProcessor($child, $this->depth + 1);
					$p->process($rtf, $sect);
					$this->toc[] = $p;
					continue;
				}
				if($child->localName == 'para' || $child->localName == 'note')
				{
					$p = new DocbookBlockProcessor($child);
					$p->process($rtf, $sect);
					continue;
				}
				trigger_error("Don't know how to process a <" . $child->localName . ">", E_USER_WARNING);
			}
		}
	}
}

class DocbookChapterProcessor extends DocbookSectionProcessor
{
	protected function section($rtf, $sect)
	{
		$sect = $rtf->section();
		$sect->break = 'page';
		return $sect;
	}
}

class DocbookBlockProcessor
{
	protected $element;
	
	public function __construct($element)
	{
		$this->element = $element;
	}
	
	public function process($rtf, $section = null, $para = null, $style = null)
	{
		if($section === null)
		{
			$section = $rtf->section();
		}
		if($this->element->localName == 'para' || $this->element->localName == 'simplepara')
		{
			return $this->processPara($rtf, $section, $para, $style);
		}
		if($this->element->localName == 'note')
		{
			$style = $rtf->style('Note');
			return $this->processChildren($rtf, $section, $para, $style);
		}
		trigger_error("Don't know how to process a <" . $child->localName . ">", E_USER_ERROR);
	}

	protected function processPara($rtf, $section, $para, $style)
	{
		$para = null;
		$this->processChildren($rtf, $section, $para, $style);		
	}

	protected function processChildren($rtf, $section, $para, $style)
	{
		for($child = $this->element->firstChild; $child; $child = $child->nextSibling)
		{
			$name = $child->localName;
			if(in_array($name, Docbook::$blocks))
			{
				$p = new DocbookBlockProcessor($child);
				$p->process($rtf, $section, $para, $style);
				$para = null;
				continue;
			}
			if($para === null)
			{
				$para = $section->para($style);
			}
			$p = new DocbookInlineProcessor($child);
			$p->process($rtf, $section, $para, $style);
		}
	}
}

class DocbookInlineProcessor
{
	protected $element;
	public $textContent;

	public function __construct($element)
	{
		$this->element = $element;
		$this->textContent = is_object($element) ? $element->textContent : $element;
	}
	
	public function process($rtf, $section, $para, $style, $charstyle = null)
	{
		if($this->element instanceof DOMText)
		{
			$span = $para->span($charstyle);
			$span->text = $this->element->wholeText;
			return;
		}
		if($this->element instanceof DOMElement)
		{
			$name = $this->element->localName;
			if($name == 'sbr')
			{
				$para->span("\n");
				return;
			}
			$role = $this->element->getAttribute('role');
			$charstyle = $rtf->charstyle($name, $role);
			$this->processChildren($rtf, $section, $para, $style, $charstyle);
			return;
		}
		print_r($this);
		throw new Exception('Element is not a DOMText nor a DOMElement');
	}

	protected function processChildren($rtf, $section, $para, $style, $charstyle)
	{
		if($this->element instanceof DOMElement)
		{
			for($child = $this->element->firstChild; $child; $child = $child->nextSibling)
			{
				$p = new DocbookInlineProcessor($child);
				$p->process($rtf, $section, $para, $style, $charstyle);
			}
		}
	}
}
