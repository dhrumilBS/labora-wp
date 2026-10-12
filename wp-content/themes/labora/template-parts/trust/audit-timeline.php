<?php
/**
 * Trust Center: example audit trail. Copied unchanged from the HTML site's security page; it illustrates the product
 * and is part of the design (not edited in WordPress).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;
?>
<ol class="timeline reveal"><li><span class="tl-ic"><svg class="icon" aria-hidden="true"><use href="#i-file"/></svg></span><div><b>Front desk registered CBC, Lipid Profile</b><span class="tl-meta">J. Ortiz · Main Lab, Austin</span></div><time>9:02:14 AM</time></li><li><span class="tl-ic"><svg class="icon" aria-hidden="true"><use href="#i-tube"/></svg></span><div><b>Sample stored in Rack B-04, slot B4</b><span class="tl-meta">A. Mehta · barcode scan</span></div><time>9:24:19 AM</time></li><li><span class="tl-ic"><svg class="icon" aria-hidden="true"><use href="#i-edit"/></svg></span><div><b>Result corrected for Hemoglobin</b><span class="tl-meta">R. Kumar · reason: transcription error</span><span class="diff"><del>13.1 g/dL</del><svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg><ins>13.6 g/dL</ins></span></div><time>10:12:44 AM</time></li><li><span class="tl-ic"><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg></span><div><b>Report signed and released</b><span class="tl-meta">Dr. S. Mitchell · pathologist</span></div><time>10:31:08 AM</time></li><li><span class="tl-ic"><svg class="icon" aria-hidden="true"><use href="#i-send"/></svg></span><div><b>Signed PDF sent to patient and referring doctor</b><span class="tl-meta">Labora · automatic delivery</span></div><time>10:31:09 AM</time></li></ol>
