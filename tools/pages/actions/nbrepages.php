<?php
     $res = $this->LoadSingle("SELECT count(distinct tag) as nbrPg FROM ".$this->config["table_prefix"]."pages ;");
     if ($res) {
         echo $res["nbrPg"];
    }
?>  
