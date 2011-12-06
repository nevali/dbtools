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

$rtf->defaultFont->name = 'Palatino-Roman';
$rtf->defaultFont->altNames = array('Palatino', 'Georgia');
$rtf->defaultFont->family = 'roman';

$palatino = $rtf->font('Palatino-Roman', 'roman', array('Palatino', 'Georgia'));
$gillSans = $rtf->font('GillSans', 'swiss', array('Gill Sans'));
$consolas = $rtf->font('Consolas', 'modern', array('Menlo', 'Courier New'));

$rtf->defaultStyle->size = 16;
$rtf->defaultStyle->leftIndent = 28;
$rtf->defaultStyle->rightIndent = 28;
$rtf->defaultStyle->spaceBefore = 12;
$rtf->defaultStyle->spaceAfter = 12;

$rtf->style('Title', $gillSans, 48);

$rtf->style('Cover-page author', $gillSans, 12, $rtf->colour(71, 20, 0));

$rtf->style('Cover-page affiliation', $gillSans, 18);

$rtf->style('Heading 1', $gillSans, 36);

$rtf->style('Heading 2', $gillSans, 18);

$s = $rtf->style('Note', $gillSans, 16, $rtf->colour(71, 20, 0));
$s->leftIndent = $s->rightIndent = 28;
$s->spaceBefore = $s->spaceAfter = 12;

$s = $rtf->charstyle('mathphrase');
$s->font = $palatino;
$s->colour = $rtf->colour(30, 30, 30);
$s->italic = true;

$s = $rtf->charstyle('type');
$s->font = $consolas;
$s = $rtf->charstyle('classname');
$s->font = $consolas;

$s = $rtf->charstyle('citation');
$s->size = 12;

$s = $rtf->charstyle('emphasis', 'brand', true);
$s->font = $palatino;
$s->italic = true;
