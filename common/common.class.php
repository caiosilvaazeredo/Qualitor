<?php

class sqlquery {

    public function getTicketInfAdd ($arrayParams) {
        $querySQL = "select "
                . "chminf.cdchamado, "
                . "a.cdtipoinformacaoadicional, "
                . "'vlinformacaoadicional'+cast(a.cdtipoinformacaoadicional as varchar(10)) as nmalias, "
                . "a.nmtipoinformacaoadicional, "
                . "isnull(case idtipodado "
                . "when 'T' then chminf.nminformacao "
                . "when 'N' then cast(cast(chminf.vlinformacao as numeric(10,2)) as varchar(30)) "
                . "when 'D' then convert(varchar(10), chminf.dtinformacao, 105) "
                . "when 'M' then cast(chminf.dsinformacao as varchar(max)) end, '') as vlinf, "
                . "chminf.nrsequencia, "
                . "a.cdclassificacao, "
                . "cast(a.cdtipoinformacaoadicional as varchar(10)) as vlinformacaoadicional, "
                . "a.idtipodado "
                . "from hd_chamadoinformacaoadicional chminf inner join hd_tipoinformacaoadicional a "
                . "on a.cdtipoinformacaoadicional = chminf.cdtipoinformacaoadicional "
                . "inner join hd_chamado chm on chminf.cdchamado = chm.cdchamado "
                . "where 1 = 1 ";

        If (isset($arrayParams['cdchamado'])) {
            $querySQL .= " and chminf.cdchamado = " . $arrayParams['cdchamado'];
        }

        If (isset($arrayParams['cdtipoinformacaoadicional'])) {
            $querySQL .= " and chminf.cdtipoinformacaoadicional in (" . $arrayParams['cdtipoinformacaoadicional'] . ")";
        }

        If (isset($arrayParams['cdsituacao'])) {
            $querySQL .= " and chm.cdsituacao in (" . $arrayParams['cdsituacao'] . ")";
        }

        $querySQL .= " order by chminf.nrsequencia";

        return $querySQL;

    }

    public function getTicketAttch ($arrayParams) {
        $querySQL = "Select 
            top 1 
                cdchamado, 
                nmanexo, 
                dsanexo, 
                dtanexo, 
                vltamanho 
            from 
                hd_anexochamado 
            where 
                isnull(cdclassificacao, 0) <> 1 ";

        If (isset($arrayParams['cdchamado'])) {
            $querySQL .= " and cdchamado = " . $arrayParams['cdchamado'];
        }

        $querySQL .= " order by nrsequencia desc";

        return $querySQL;
    }
}