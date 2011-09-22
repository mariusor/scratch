<?php
/**
 * @package vsc_presentation
 * @subpackage views
 * @author marius orcsik <marius@habarnam.ro>
 * @date 2010.04.09
 */
import ('presentation/views');
class vscCssView extends vscStaticFileView {
	protected $sContentType = 'text/css';
	protected $sFolder = 'css';
}