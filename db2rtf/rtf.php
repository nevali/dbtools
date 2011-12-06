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

class RTFFont
{
	public $name;
	public $altNames;
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
		if(!is_array($this->altNames) && strlen($this->altNames))
		{
			$this->altNames = array($this->altNames);
		}
		if(is_array($this->altNames) && count($this->altNames))
		{
			foreach($this->altNames as $alt)
			{
				$s .= '{\*\falt ' . $alt . '}';
			}
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
	public $italic;

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
		if($this->italic !== null)
		{
			$s .= '\i' . ($this->italic ? '' : '0');
		}
		if(strlen($s))
		{
			return $s . ' ';
		}
		return '';
	}
}

class RTFSpan extends RTFStyle
{
	public $text;

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
			return '{' . $s . $me . '}';
		}
		return $me;
	}
}

class RTFPara
{
	public $style;
	protected $nodes = array();
	
	public function __construct($style)
	{
		$this->style = $style;
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
		fwrite($f, "\n" . '{\pard ' . $this->style . implode('', $this->nodes) . '\par}');
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

	public function para($style = null)
	{
		if($style === null)
		{
			$style = $this->style;
		}
		$this->paras[] = $p = new RTFPara($style);
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

class RTF
{
	protected $fontTable = array();
	protected $colourTable = array();
	protected $styles = array();
	protected $sections = array();
	protected $charstyles = array();

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

	public function font($name = 'default', $family = null, $altNames = null)
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
		$this->fontTable[$k] = $f = new RTFFont($name, $family, $altNames, $n);
		return $f;
	}

	public function colour($r = 0, $g = 0, $b = 0)
	{
		$n = count($this->colourTable);
		$this->colourTable[] = $c = new RTFColour($r, $g, $b, $n);
		return $c;
	}

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
		fwrite($f, '}' . "\n");
		fclose($f);
	}
	
	protected function writeHeader($f)
	{
		fwrite($f, '{\rtf1\ansi\ansicpg1252\cocoartf1138\cocoasubrtf230' . "\n");
		$this->writeFonts($f);
		$this->writeColours($f);
		$this->writeInfo($f);
		$this->writeControl($f);
	}
	
	protected function writeFonts($f)
	{
		fwrite($f, '{\fonttbl');
		foreach($this->fontTable as $id => $font)
		{
			fwrite($f, $font);
		}
		fwrite($f, '}' . "\n");
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
		fwrite($f, '}' . "\n");
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
