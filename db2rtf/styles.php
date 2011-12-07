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

$rtf->paper = array(11907, 16840);
$rtf->margins = array(1440, 1440, 1800, 1800);
$rtf->widowsOrphans = true;
$rtf->viewKind = 1;
$rtf->viewZoomKind = 1;

$rtf->defaultFont->name = 'Palatino';
$rtf->defaultFont->altName = 'Palatino Linotype';
$rtf->defaultFont->family = 'roman';

$palatino = $rtf->font('Palatino', 'roman');
$gillSans = $rtf->font('Gill Sans', 'swiss');
$gillSansLight = $rtf->font('Gill Sans Light', 'swiss');
$consolas = $rtf->font('Consolas', 'modern');

$rtf->defaultStyle->size = 16;
$rtf->defaultStyle->leftIndent = 28;
$rtf->defaultStyle->rightIndent = 28;
$rtf->defaultStyle->spaceBefore = 12;
$rtf->defaultStyle->spaceAfter = 12;

$rtf->style('Title', $gillSans, 48);

$rtf->style('Cover-page author', $gillSans, 12, $rtf->colour(71, 20, 0));

$rtf->style('Cover-page affiliation', $gillSans, 18);

$s = $rtf->style('Heading 1', $gillSans, 36);
$s->keepWithNext = true;
$s->leftIndent = -4;

$s = $rtf->style('Heading 2', $gillSans, 18);
$s->keepWithNext = true;

$s = $rtf->style('TOC Level 1', $gillSans, 18);
$s->spaceBefore = $s->spaceAfter = 6;

$s = $rtf->style('TOC Level 2', $gillSans, 12);
$s->leftIndent = 28;
$s->rightIndent = 28;
$s->spaceBefore = $s->spaceAfter = 6;

$s = $rtf->style('TOC Level 3', $gillSansLight, 12);
$s->leftIndent = 28;
$s->rightIndent = 28;
$s->spaceBefore = $s->spaceAfter = 6;

$s = $rtf->style('TOC Level 4', $gillSansLight, 12);
$s->leftIndent = 36;
$s->rightIndent = 36;
$s->spaceBefore = $s->spaceAfter = 6;

$s = $rtf->style('Note', $gillSans, 16, $rtf->colour(71, 20, 0));
$s->leftIndent = $s->rightIndent = 28;
$s->spaceBefore = $s->spaceAfter = 12;
$s->keepWithNext = true;

$s = $rtf->charstyle('mathphrase');
$s->font = $palatino;
$s->colour = $rtf->colour(30, 30, 30);
$s->italic = true;

$s = $rtf->charstyle('type');
$s->font = $consolas;
$s->size = 14;

$s = $rtf->charstyle('phrase', 'rfc2119', true);
$s->font = $gillSans;
$s->allCaps = true;
$s->size = 14;

$s = $rtf->charstyle('classname');
$s->font = $consolas;

$s = $rtf->charstyle('citation');
$s->size = 12;

$s = $rtf->charstyle('emphasis', 'brand', true);
$s->font = $palatino;
$s->italic = true;

$t = $rtf->listTemplate();
$t->style->font = $palatino;
$t->style->size = 16;
$t->style->leftIndent = 56;
$t->style->firstIndent = -18;
$t->style->rightIndent = 56;
$t->style->spaceBefore = 6;
$t->style->spaceAfter = 6;
$t->style->keepTogether = true;
