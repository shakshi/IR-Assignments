<?php
    header('Content-Type: text/html; charset=utf-8');
ini_set('memory_limit', -1);
include 'simple_html_dom.php';
include 'SpellCorrector.php';


    $limit = 10;
    $query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
    $results = false;
    $algo= isset($_REQUEST["algo"]) ? $_REQUEST["algo"] : false;
    
    $correct_query="";
$flag = 0;

    if ($query)
    {
		require_once('Apache/Solr/Service.php');
		$solr = new Apache_Solr_Service('localhost', 8983, '/solr/hg/');

		$query_terms = explode(" ", $query);
		
		if (get_magic_quotes_gpc() == 1){
			$query = stripslashes($query);
		}
		
		  for($i = 0 ; $i < sizeof($query_terms); $i++)
		  {
		  	$chk = SpellCorrector::correct($query_terms[$i]);
		  	if($i == 0)
		  		$correct_query = $correct_query . $chk;
		  	else
		  		$correct_query = $correct_query .' '. $chk;
		  }
		  
		if(strtolower($query) != strtolower($correct_query))
		  {
		  	$flag = 1;
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
	<link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="http://code.jquery.com/jquery-1.10.2.js"></script>

    <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    </head>
    <body>
	<!-- style="margin: 4px; font-size:16px" -->
        <h1 style="text-align:center; color:brown"> Reuters Search Engine </h1>
        <div>
		<!--<?php echo SpellCorrector::correct('octabr');
//it will output *october* ?> -->

            <form  accept-charset="utf-8" method="get" >
                <label for="q">Search:</label>
                <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
                <br>
		<br>
                <input id="l" type="radio" name="algo" value = "l"
                    <?php echo "checked";?>>Lucene(Default)
                <input id="pr" type="radio" name="algo" value="pr" 
                    <?php if (isset($algo) && $algo=="pr") echo "checked";?> > PageRank   
		<br><br>                
		<input type="submit"/>
            </form>
        </div>
<script>
$(function() {
	
     var prefix = "http://localhost:8983/solr/hg/suggest?q=";
     var suffix = "&wt=json&indent=true";
     var words = [];
     
	 $("#q").autocomplete({
       source : function(request, response) {
         var correct="",before="";
         var query = $("#q").val().toLowerCase();
         var space =  query.lastIndexOf(' ');
         
		if(query.length-1>space && space!=-1){
          correct=query.substr(space+1);
          before = query.substr(0,space);
        }
        else{
          correct=query.substr(0); 
        }

		console.log("before:", + before)
		console.log("query:", + correct)

        var URL = prefix + correct+  suffix;

        $.ajax({
         url : URL,
         success : function(data) {
          var js =data.suggest.suggest;
          var docs = JSON.stringify(js);
          var jsonData = JSON.parse(docs);
          var result =jsonData[correct].suggestions;
          var suggestions =[];

		  var j=0;
          for(var i=0;i<5 && j<result.length;i++,j++){
            
	    if(result[j].term==correct)
            {
              i--;
              continue;
            }
            
		for(var k=0;k<i && i>0;k++){
              if(words[k].indexOf(result[j].term) >=0){
                i--;
                continue;
              }
            }
            
		if(result[j].term.indexOf('.')>=0 || result[j].term.indexOf('_')>=0)
            {
              i--;
              continue;
            }
            var s =(result[j].term);
            if(suggestions.length == 5)
              break;
            if(suggestions.indexOf(s) == -1)
            {
              suggestions.push(s);
              if(before==""){
                words[i]=s;
              }
              else
              {
                words[i] = before+" ";
                words[i]+=s;
              }
            }
          }
          console.log(words);
          response(words);
        },
        dataType : 'jsonp',
        jsonp : 'json.wrf'
      });
      },
      minLength : 1
    })
   });
 </script>

        <?php
	   $csvArray =  array_map('str_getcsv', file("URLtoHTML_reuters_news.csv"));
		$count =0;
		$pre="";
            // display results
            if ($results)
            {
				$total = (int) $results->response->numFound;
				$start = min(1, $total);
				$end = min($limit, $total);

				 if($flag == 1){
				  	
					$link = "http://localhost/searchEngine.php?q=$correct_query";
					echo "<i>Did u mean <a href='$link'>$correct_query</a></i>";
					
					echo "<br><br>Showing results for ", ucwords($query);
				}

				//creating an array of doc ids and their urls from Csv file
				$csv= file("URLtoHTML_reuters_news.csv");
				$urlarray = array();

				foreach($csv as $line){
					$line= str_getcsv($line);
					$urlarray[$line[0]]= trim($line[1]);
				}    
        ?>
        <div style="margin:0px;font-size:15px;">
            <div><br><b>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</b></div>
            <ol >
                <?php
                    // iterate result documents
                    foreach ($results->response->docs as $doc)
                    {
                    $url="";
                ?>
                <li>
                    <table style=" margin: 4px;border: 1px solid; text-align: left;font-size:15px;"> 
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
					if (is_array($docTitle)){
						$docTitle= $docTitle[0];
					}

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
                             
		        $html = file_get_contents($docId);
			$html = str_get_html($html);
			$html =  $html->plaintext;

			$sentences = explode(".", $html);
			$words = explode(" ", $query);

			$snippet = "";
			$text = "/";
			$start_delim="(?=.*?\b";
			$end_delim="\b)";
			foreach($words as $item){
			  $text=$text.$start_delim.$item.$end_delim;
			}
			$text=$text."^.*$/i";


			$i=1;
			$start="...";
			foreach($sentences as $sentence){
			  
			  $sentence=strip_tags($sentence);

			  if (preg_match($text, $sentence)>0){
				if (preg_match("(&gt|&lt|\/|{|}|[|]|\|\%|>|<|:)",$sentence)>0){
				  continue;
				}
				else{
					if($i==1){
						$start="";
					}
		
				  $snippet = $snippet.$sentence.". ";
				  if(strlen($snippet)>160) 
					break;
				}
			  }
			  
			  $i=$i+1;
			}
			$words = preg_split('/\s+/', $query);

			foreach($words as $item)
			$snippet = str_ireplace($item, "<strong>".$item."</strong>",$snippet);
			if($snippet == ""){
			  $snippet = "N/A";
			}
			else{
				$snippet=$start.$snippet;
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
			<td> Snippet:</td>
			<td><?php 
            if($snippet == "N/A"){
              echo htmlspecialchars($snippet, ENT_NOQUOTES, 'utf-8');
            }else{
              echo $snippet."...";
            }
            ?></td>
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
