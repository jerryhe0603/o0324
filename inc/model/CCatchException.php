<?php

//format 1:
$aCatchException = array(	$iElementMappingNo1 => array(	$iSiteNo1_1=>$sRegex1_1,
															$iSiteNo1_2=>$sRegex1_2,
															$iSiteNo1_3=>$sRegex1_3,
															),
							$iElementMappingNo2 => array(	$iSiteNo2_1=>$sRegex2_1,
															$iSiteNo2_2=>$sRegex2_2,
															$iSiteNo2_3=>$sRegex2_3,
															),
							$iElementMappingNo3 => array(	$iSiteNo3_1=>$sRegex3_1,
															$iSiteNo3_2=>$sRegex3_2,
															$iSiteNo3_3=>$sRegex3_3,
															)
							);



//format 2:
$aCatchException = array(	array(	"element_mapping_no" => $iElementMappingNo1,
									"exception" => array(	array(	"site_no" => $iSiteNo1_1,
																	"regex" => $sRegex1_1
																	),
															array(	"site_no" => $iSiteNo1_2,
																	"regex" => $sRegex1_2
																	),
															array(	"site_no" => $iSiteNo1_3,
																	"regex" => $sRegex1_3
																	)
															),
									),
							);

?>