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

/* Represents an RTF document */
class RTF
{
	protected $fontTable = array();
	protected $colourTable = array();
	protected $styles = array();
	protected $sections = array();
	protected $charstyles = array();
	protected $listTable = array();
	protected $listOverridesTable = array();

	public $defaultFont;
	public $bg;
	public $defaultStyle;
	public $sectionDefaults;
	protected $fg;

	public $title = null;
	public $author = null;
	public $company = null;
	public $subject = null;
	public $comments = null;
	public $copyright = null;
	public $keywords = null;
	
	public $viewKind = null;
	public $viewZoom = null;
	public $viewZoomKind = null;
	public $paper = null;
	public $margins = null;
	public $facingPages = false;
	public $landscape = false;
	public $widowsOrphans = false;
	public $immediateBreak = false;

	public function __construct()
	{
		$this->defaultFont = $this->fontTable['default'] = new RTFFont('Helvetica', 'swiss', null, 0);
		$this->fg = $this->colour(0, 0, 0);
		$this->bg = $this->colour(255, 255, 255);
		$this->defaultStyle = $this->style('Body');
		$this->sectionDefaults = new RTFSection($this->defaultStyle);
		$this->charstyle('emphasis')->italic = true;
	}
	
	/* Obtain an RTFFont instance, adding it to the font table if necessary */
	public function font($name = 'default', $family = null, $altName = null)
	{
		if(!strcmp($name, 'default') && !strlen($family))
		{
			return $this->fontTable['default'];
		}
		$k = $name . '/' . $family;
		if(isset($this->fontTable[$k]))
		{
			return $this->fontTable[$k];
		}
		$n = count($this->fontTable);
		$this->fontTable[$k] = $f = new RTFFont($name, $family, $altName, $n);
		return $f;
	}

	/* Obtain an RTFColour instance, adding it to the colour table if necessary */
	public function colour($r = 0, $g = 0, $b = 0)
	{
		$n = count($this->colourTable);
		$this->colourTable[] = $c = new RTFColour($r, $g, $b, $n);
		return $c;
	}

	/* Obtain an RTFStyle instance, adding it to the style list if it
	 * does not already exist.
	 */
	public function style($name, $font = null, $size = 12, $colour = null)
	{
		if(isset($this->styles[$name]))
		{
			return $this->styles[$name];
		}
		if($font === null)
		{
			$font = $this->defaultFont;
		}
		if($colour === null)
		{
			$colour = $this->fg;
		}
		$this->styles[$name] = $s = new RTFStyle($font, $size, $colour);
		return $s;
	}

	/* Obtain an RTFSpan instance representing a character style, adding it
	 * to the character style list if needed. If explicit creation of a
	 * subclass is desired, specify both $class and $createClass.
	 */
	public function charstyle($name, $class = null, $createClass = false)
	{
		if(strlen($class))
		{
			if(isset($this->charstyles[$name . '.' . $class]))
			{
				return $this->charstyles[$name . '.' . $class];
			}
			if($createClass)
			{
				$this->charstyles[$name . '.' . $class] = $s = new RTFSpan();
				return $s;
			}
		}
		if(isset($this->charstyles[$name]))
		{
			return $this->charstyles[$name];
		}
		$this->charstyles[$name] = $s = new RTFSpan();
		return $s;
	}

	/* Create a new RTFSection instance with the given style (falling back
	 * to the default style if none is specified) and add it to the
	 * section list.
	 */
	public function section($style = null)
	{
		if($style === null)
		{
			$style = $this->defaultStyle;
		}
		$sect = clone $this->sectionDefaults;
		$sect->style = $style;
		$this->sections[] = $sect;
		return $sect;
	}

	/* Obtain an RTFListTemplate instance and add it to the list table if
	 * necessary.
	 */
	public function listTemplate($name = 'Bullets')
	{
		if(!isset($this->listTable[$name]))
		{
			$style = $this->style($name);
			$index = count($this->listTable) + 1;
			$template = new RTFListTemplate($index, $style);
			$style->listTemplate = $template;
			$this->listTable[$name] = $template;
			$index = count($this->listOverridesTable) + 1;
			$this->listOverridesTable[$name] = new RTFListOverride($index, $this->listTable[$name]);
			$this->listTable[$name]->defaultOverrideIndex = $index;
		}
		return $this->listTable[$name];
	}

	/* Write the document to a file */
	public function write($filename)
	{
		$f = fopen($filename, 'w');
		if($f === false)
		{
			return false;
		}
		$this->writeHeader($f);
		$ld = true;
		foreach($this->sections as $sect)
		{
			if(!$ld && $sect->break == 'page' && !$sect->hide)
			{
//				fwrite($f, '\page');
			}
			if($sect->write($f))
			{
				$ld = false;
			}
		}
		fwrite($f, '}');
		fclose($f);
	}
	
	protected function writeHeader($f)
	{
		fwrite($f, '{\rtf1\ansi\ansicpg1252\cocoartf1138\cocoasubrtf230' . "\n");
		$this->writeFonts($f);
		$this->writeColours($f);
		$this->writeInfo($f);
		$this->writeControl($f);
		$this->writeListTemplates($f);
	}
	
	protected function writeFonts($f)
	{
		fwrite($f, '{\fonttbl');
		foreach($this->fontTable as $id => $font)
		{
			fwrite($f, $font);
		}
		fwrite($f, '}');
	}
	
	protected function writeListTemplates($f)
	{
		if(count($this->listTable))
		{
			fwrite($f, '{\*\listtable');
			fwrite($f, implode('', $this->listTable));
			fwrite($f, '}');
		}
		if(count($this->listOverridesTable))
		{
			fwrite($f, '{\listoverridetable');
			fwrite($f, implode('', $this->listOverridesTable));
			fwrite($f, '}');
		}
	}
	
	protected function writeColours($f)
	{
		fwrite($f, '{\colortbl');		
		foreach($this->colourTable as $id => $col)
		{
			if($id > 0)
			{
				fwrite($f, '\red' . $col->r . '\green' . $col->g . '\blue' . $col->b);
			}
			fwrite($f, ';');
		}
		fwrite($f, '}');
	}

	protected function writeInfo($f)
	{
		$block = array();
		if($this->title !== null)
		{
			$block[] = '{\title ' . addslashes($this->title) . '}';
		}
		if($this->author !== null)
		{
			$block[] = '{\author ' . addslashes($this->author) . '}';
		}
		if($this->company !== null)
		{
			$block[] = '{\*\company ' . addslashes($this->company) . '}';
		}
		if($this->subject !== null)
		{
			$block[] = '{\subject ' . addslashes($this->subject) . '}';
		}
		if($this->comments !== null)
		{
			$block[] = '{\doccom ' . addslashes($this->comments) . '}';
		}
		if($this->copyright !== null)
		{
			$block[] = '{\*\copyright ' . addslashes($this->copyright) . '}';
		}
		if($this->keywords !== null)
		{
			$block[] = '{\keywords ' . addslashes($this->keywords) . '}';
		}
		fwrite($f, '{\info' . "\n" . implode("\n", $block) . '}');
	}
	
	protected function writeControl($f)
	{
		if($this->viewKind !== null)
		{
			fwrite($f, '\viewkind' . intval($this->viewKind));
		}
		if($this->viewZoom !== null)
		{
			fwrite($f, '\viewscale' . intval($this->viewZoom));
		}
		if($this->viewZoomKind !== null)
		{
			fwrite($f, '\viewzk' . intval($this->viewZoomKind));
		}
		if($this->paper !== null)
		{
			fwrite($f, '\paperw' . $this->paper[0] . '\paperh' . $this->paper[1]);
		}
		if($this->margins !== null)
		{
			fwrite($f, '\margl' . $this->margins[0] . '\margr' . $this->margins[1]);
		}
		if($this->facingPages)
		{
			fwrite($f, '\facingp');
		}
		if($this->landscape)
		{
			fwrite($f, '\landscape');
		}
		if($this->widowsOrphans)
		{
			fwrite($f, '\widowctl');	   
		}
		if($this->immediateBreak)
		{
			fwrite($f, '\spltpgpar');
		}
	}
}

class RTFFont
{
	public $name;
	public $altName;
	public $family; /* nil, roman, swiss, modern, script, decor, tech, bidi */
	public $index;
	public $charset;

	public function __construct($name, $family, $altNames, $index)
	{
		$this->name = $name;
		$this->family = $family;
		$this->altNames = $altNames;
		$this->index = $index;
		if(!strlen($this->family))
		{
			$this->family = 'nil';
		}
	}

	public function __toString()
	{
		$s = '\f' . $this->index . '\f' . $this->family;
		if($this->charset !== null)
		{
			$s .= '\fcharset' . $this->charset;
		}
		$s .= ' ' . $this->name;
		if($this->altName !== null)
		{
			$s .= '{\*\falt ' . $this->altName . '}';
		}
		$s .= ';';
		return $s;
	}   
}

class RTFColour
{
	public $r;
	public $g;
	public $b;
	public $index;

	public function __construct($r, $g, $b, $index)
	{
		$this->r = $r;
		$this->g = $g;
		$this->b = $b;
		$this->index = $index;
	}
}

class RTFStyle
{
	public $font;
	public $colour;
	public $size;
	public $pardir;
	public $justify;
	public $spaceBefore;
	public $spaceAfter;
	public $firstIndent;
	public $leftIndent;
	public $rightIndent;
	public $keepTogether;
	public $keepWithNext;
	public $widowsOrphans;
	public $breakBefore;
	public $hyphenation;
	public $bold;
	public $italic;
	public $allCaps;
	public $tabStops = array(220, 720, 1133, 1700, 2267, 2834, 3401, 3968, 4535, 5102, 5669, 6236, 6803, 6803);
	public $listTemplate;

	public function __construct($font, $size, $colour)
	{
		$this->font = $font;
		$this->colour = $colour;
		$this->size = $size;
		$this->pardir = 'natural';
	}

	public function __toString()
	{
		$s = '';
		if($this->font !== null)
		{
			$s .= '\\f' . $this->font->index;
		}
		if($this->size !== null)
		{
			$s .= '\\fs' . ($this->size * 2);
		}
		if($this->colour !== null)
		{
			$s .= '\\cf' . $this->colour->index;
		}
		if($this->pardir !== null)
		{
			$s .= '\\pardir' . $this->pardir;
		}
		if($this->keepTogether !== null)
		{
			$s .= '\\keep';
		}
		if($this->keepWithNext !== null)
		{
			$s .= '\\keepn';
		}
		if($this->justify !== null)
		{
			switch($this->justify)
			{
			case 'left':
				$s .= '\ql';
				break;
			case 'right':
				$s .= '\qr';
				break;
			case 'center':
			case 'middle':
			case 'centre':
				$s .= '\qc';
				break;
			case 'full':
			case 'justify':
				$s .= '\qc';
			}
		}
		if($this->spaceBefore !== null)
		{
			$s .= '\sb' . round($this->spaceBefore * 20);
		}
		if($this->spaceAfter !== null)
		{
			$s .= '\sa' . round($this->spaceAfter * 20);
		}
		if($this->listTemplate === null)
		{
			if($this->firstIndent !== null)
			{
				$s .= '\fi' . round($this->firstIndent * 20);
			}
			if($this->leftIndent !== null)
			{
				$s .= '\li' . round($this->leftIndent * 20);
			}
			if($this->rightIndent !== null)
			{
				$s .= '\ri' . round($this->rightIndent * 20);
			}
		}
		if($this->italic !== null)
		{
			$s .= '\i' . ($this->italic ? '' : '0');
		}
		if($this->bold !== null)
		{
			$s .= '\b' . ($this->bold ? '' : '0');
		}
		if($this->allCaps !== null)
		{
			$s .= '\caps' . ($this->allCaps ? '' : '0');
		}
		if(is_array($this->tabStops) && count($this->tabStops))
		{
			foreach($this->tabStops as $stop)
			{
				$s .= '\tx' . $stop;
			}
		}
		if(strlen($s))
		{
			return $s;
		}
		return '';
	}
}

class RTFSpan extends RTFStyle
{
	public $text;
	public $tabStops = null;

	public function __construct($text = null)
	{
		$this->text = $text;
	}

	public static function escape($text, $charset = 'windows-1252')
	{
		$text = iconv('UTF-8', $charset . '//IGNORE//TRANSLIT', $text);
		$out = '';
		$l = strlen($text);
		for($c = 0; $c < $l; $c++)
		{
			$ch = $text[$c];
			if($ch == '\\')
			{
				$out .= '\\\\';
			}
			else if($ch == "\n" || $ch == '{' || $ch == '}')
			{
				$out .= '\\' . $ch;
			}
			else if(ord($ch) < 32 || ord($ch) > 126)
			{
				$out .= '\\\'' . sprintf('%02x', ord($ch));
			}
			else
			{
				$out .= $ch;
			}
		}
		return $out;
	}

	public function __toString()
	{
		$s = parent::__toString();
		$me = self::escape($this->text);
		if(strlen($s))
		{
			return '{' . $s . ' ' . $me . '}';
		}
		return $me;
	}
}

class RTFPara
{
	public $style;
	protected $nodes = array();
	public $listTemplate;
	public $indentLevel;

	public function __construct(RTFStyle $style, $indentLevel)
	{
		$this->style = $style;
		$this->indentLevel = $indentLevel;
	}

	public function span($text = 'The quick brown fox jumps over the lazy dog.')
	{
		if($text instanceof RTFSpan)
		{
			$s = clone $text;
			$s->text = null;
		}
		else
		{
			$s = new RTFSpan($text);
		}
		$this->nodes[] = $s;
		return $s;
	}

	public function add(RTFSpan $span)
	{
		$this->nodes[] = clone $span;
	}
	
	public function write($f)
	{
		$extra = array();
		if($this->style->listTemplate !== null)
		{
			$extra[] = '\ls' . $this->style->listTemplate->defaultOverrideIndex;
		}
		if($this->indentLevel !== null)
		{
			$extra[] = '\ilvl' . $this->indentLevel;
		}
		if($this->style->listTemplate !== null)
		{
			$extra[] = $this->style->listTemplate->paragraphAdditions();
		}
		if($this->style->listTemplate !== null)
		{
			$prefix = '{\listtext ' . $this->style->listTemplate->compatText . '}';
		}
		else
		{
			$prefix = null;
		}
		if(count($extra))
		{
			$extra = implode('', $extra);
		}
		else
		{
			$extra = '';
		}
		fwrite($f, "\n" . '{\pard' . $this->style . $extra . ' ' . $prefix . implode('', $this->nodes) . '\par}');
	}
}

class RTFSection
{
	public $style; /* Style which will be applied to all paragraphs by default */
	protected $paras = array();	
	public $break = 'none'; /* none, page, col, even, odd */
	public $paper;
	public $margins;
	public $viewport;
	public $verticalAlign; /* t, b, c */
	public $hide = false;
	public $pageNumberingStart = null;

	public function __construct($style)
	{
		$this->style = $style;
	}

	public function para($style = null, $indentLevel = 0)
	{
		if($style === null)
		{
			$style = $this->style;
		}
		if(!($style instanceof RTFStyle))
		{
			trigger_error('Argument 1 passed to ' . get_class($this) . '::' . __FUNCTION__ . ' must be an instance of RTFStyle, ' . get_class($style) . ' given.', E_USER_ERROR);
		}
		$this->paras[] = $p = new RTFPara($style, $indentLevel);
		return $p;
	}
	
	public function write($f)
	{
		if($this->hide || !count($this->paras))
		{
			return false;
		}
		fwrite($f, '{\sectd');
		if($this->break !== null)
		{
			fwrite($f, '\sbk' . $this->break);
		}
		if($this->paper !== null)
		{
			fwrite($f, '\paperw' . $this->paper[0] . '\paperh' . $this->paper[1]);
		}
		if($this->margins !== null)
		{
			fwrite($f, '\margl' . $this->margins[0] . '\margr' . $this->margins[1]);
		}
		if($this->verticalAlign !== null)
		{
			fwrite($f, '\vertal' . $this->verticalAlign);
		}
		if($this->pageNumberingStart !== null)
		{
			fwrite($f, '\linestarts' . $this->pageNumberingStart . '\linerestart');
		}
		foreach($this->paras as $para)
		{
			$para->write($f);
		}
		fwrite($f, '\sect}');
		return true;
	}
}

class RTFListTemplate 
{
	public $index;
	protected $levels = array();
	public $simple = false;	
	public $defaultOverrideIndex = 0;
	public $compatText = '\\\'95\\tab';
	public $style;

	public function __construct($index, RTFStyle $style)
	{
		$this->style = $style;
		$this->index = $index;
		for($c = 0; $c < 10; $c++)
		{
			$this->levels[$c] = new RTFListLevel($index, $style);
		}
	}

	public function level($index)
	{
		if($index < count($this->levels))
		{
			return $this->levels[$index];
		}
		return null;
	}

	public function paragraphAdditions($level = 0)
	{
		return $this->levels[$level]->paragraphAdditions();
	}
	
	public function __toString()
	{
		$s = array();
		$s[] = '{\list\listtemplateid' . $this->index;
		if($this->simple)
		{
			$s[] = '\listsimple';
		}
		else
		{
			$s[] = '\listhybrid';
		}
		if($this->simple)
		{
			$s[] = $this->levels[0];
		}
		else
		{
			$s[] = implode('', $this->levels);
		}
		$s[] = '\listid' . $this->index;
		$s[] = '}';
		return implode('', $s);
	}
}

class RTFListLevel
{
	public $templateIndex;
	public $numberType = 23;
	public $justification = 2; /* 0 = left, 1 = centre, 2 = right */
	public $startAt = 1;
	public $follow = 0;
	public $levelText;
	public $levelNumber;
	public $style;
	public $levelSpace;
	public $levelIndent;
	public $levelMarker = 'disc';

	public function __construct($templateId, RTFStyle $style)
	{
		$this->templateIndex = $templateId;
		$this->style = $style;
	}

	public function __toString()
	{
		$s = array();
		$s[] = '{\listlevel';
		$s[] = '\levelnfc' . $this->numberType;
		$s[] = '\leveljc' . $this->justification;
		$s[] = '\levelstartart' . $this->startAt;
		$s[] = '\levelfollow' . $this->follow;
		if($this->style->firstIndent !== null)
		{
			$s[] = '\fi' . round($this->style->firstIndent * 20);
		}
		if($this->style->leftIndent !== null)
		{
			$s[] = '\li' . round($this->style->leftIndent * 20);
		}
		if($this->style->rightIndent !== null)
		{
			$s[] = '\ri' . round($this->style->rightIndent * 20);
		}
		if($this->levelSpace !== null)
		{
			$s[] = '\levelspace' . $this->levelSpace;
		}
		if($this->levelIndent !== null)
		{
			$s[] = '\levelindent' . $this->levelIndent;
		}
		if($this->levelMarker !== null)
		{
			$s[] = '{\*\levelmarker \{' . $this->levelMarker . '\}}';
		}
		$s[] = '{\leveltext \leveltemplateid' . $this->templateIndex . '\\\'01\uc0\u8226 ;}';
		$s[] = '{\levelnumbers ;}';
		$s[] = '}';
		return implode('', $s);
	}

	public function paragraphAdditions()
	{
		$s = array();
		if($this->style->firstIndent !== null)
		{
			$s[] = '\fi' . round($this->style->firstIndent * 20);
		}
		if($this->style->leftIndent !== null)
		{
			$s[] = '\li' . round($this->style->leftIndent * 20);
		}
		if($this->style->rightIndent !== null)
		{
			$s[] = '\ri' . round($this->style->rightIndent * 20);
		}
		return implode('', $s);
	}
}

class RTFListOverride
{
	public $index;
	public $template;
	public $overrides = array();

	public function __construct($index, $template)
	{
		$this->index = $index;
		$this->template = $template;
	}

	public function __toString()
	{
		$s = array();
		$s[] = '{\listoverride';
		$s[] = '\listid' . $this->template->index;
		$s[] = '\listoverridecount' . count($this->overrides);
		$s[] = '\ls' . $this->index;
		$s[] = '}';
		return implode('', $s);
	}
}
