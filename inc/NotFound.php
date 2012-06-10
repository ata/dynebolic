<?php
class NotFound extends Dashboard{
	function addContent(){
		$this->page .= <<<EOD
<div class="maincontent">
					<div id="content">
						<h3 style="color:#800303">Error..  Page Not Found... !!!</h3>
					</div>
				</div>
				
EOD;
	}
}
