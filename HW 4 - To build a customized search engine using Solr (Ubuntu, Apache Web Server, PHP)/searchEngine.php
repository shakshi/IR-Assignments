<?php
    header('Content-Type: text/html; charset=utf-8');
    
    $limit = 10;
    $query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
    $results = false;
    $algo= isset($_REQUEST["algo"]) ? $_REQUEST["algo"] : false;
    
    if ($query)
    {
		require_once('Apache/Solr/Service.php');
		$solr = new Apache_Solr_Service('localhost', 8983, '/solr/hg/');

		if (get_magic_quotes_gpc() == 1){
			$query = stripslashes($query);
		}

		$additionalParameters = array('sort'=>'pageRankFile desc');
		try
		{
			if($algo=="l"){
				$results = $solr->search($query, 0, $limit);
			}
			else{
				$results = $solr->search($query, 0, $limit, $additionalParameters);
			}
		}
		catch (Exception $e)
		{
			die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
		}
    }
    
    ?>
<html>
    <head>
        <title>Reuters Search Engine</title>
    </head>
    <body style="margin: 4px; font-size:16px">
        <h1 style="text-align:center; color:brown"> Reuters Search Engine </h1>
        <div style="text-align:center; margin:0px">
            <form  accept-charset="utf-8" method="get" style="margin:0px">
                <label for="q">Search:</label>
                <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
                <br>
                <label > Algorithm: </label>
                <input id="l" type="radio" name="algo" value = "l"
                    <?php echo "checked";?>>Lucene(Default)
                <input id="pr" type="radio" name="algo" value="pr" 
                    <?php if (isset($algo) && $algo=="pr") echo "checked";?> > PageRank   
                <input type="submit"/>
            </form>
        </div>
        <?php
            // display results
            if ($results)
            {
				$total = (int) $results->response->numFound;
				$start = min(1, $total);
				$end = min($limit, $total);

				//creating an array of doc ids and their urls from Csv file
				$csv= file("URLtoHTML_reuters_news.csv");
				$urlarray = array();

				foreach($csv as $line){
					$line= str_getcsv($line);
					$urlarray[$line[0]]= trim($line[1]);
				}    
        ?>
        <div style="margin:0px;font-size:15px;">
            <div><b>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</b></div>
            <ol >
                <?php
                    // iterate result documents
                    foreach ($results->response->docs as $doc)
                    {
                    $url="";
                ?>
                <li>
                    <table style=" margin: 1px; text-align: left;font-size:15px;">
                        <?php
                            $docId = "N/A";
                            $docUrl="N/A";
                            $docDesc = "N/A";
                            $docTitle = "N/A";
                            foreach ($doc as $field => $value) { 
                            	if($field== "id" ){
                            		$docId=$value;
                            	}
                            	if($field == "title"){
                            		$docTitle=$value;
                            	}
                            	if($field == "og_description"){
                            		$docDesc=$value;
                            	}
                            	if($field == "og_url"){
                            		$docUrl=$value;
                            	}
                            } 
                            
                            if($docUrl == "N/A"){
								$strarr = explode("/", $doc->id);
								$filename= end($strarr);
								$docUrl = $urlarray[$filename];
                            }
                            
                            ?>
                        <tr>
                            <td> Title: </td>
                            <td> <b> <a href= "<?php echo $docUrl ?>" > <?php echo $docTitle ?> </a> </b> </td>
                        </tr>
                        <tr>
                            <td> URL: </td>
                            <td> <a  href="<?php echo $docUrl?>"> <?php echo $docUrl ?> </a></td>
                        </tr>
                        <tr>
                            <td> Doc Id: </td>
                            <td> <?php echo $docId ?></td>
                        </tr>
                        <tr>
                            <td> Description: </td>
                            <td> <?php echo $docDesc ?> </td>
                        </tr>
                    </table>
                </li>
                <?php
                    } 
                    ?>
            </ol>
            <?php
                }
                ?>
        </div>
    </body>
</html>