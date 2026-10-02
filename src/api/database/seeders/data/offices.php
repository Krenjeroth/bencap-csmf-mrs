<?php

/*
|--------------------------------------------------------------------------
| Offices
|--------------------------------------------------------------------------
|
| Codes and names as provided in the specification (typos kept as given,
| decided 2026-10-02), plus four offices that have services in the 2026
| Citizen's Charter but were missing from the list: OG-PESO, OG-BAC,
| OG-CAO and OG-SDO. Order follows the 2026 charter.
|
| OG-OPA and OSMP from the specification are left out: OG-OPA is the same
| office as OG, and OSMP the same as OSSP (playbook Q5, decided 2026-10-02).
|
| Every OG-* office sits under OG (playbook Q5), OG-IT and OG-Records included.
|
| [code, slug, name, parent code or null]
|
*/

return [
    ['OG', 'og', 'Office of the Governor (OG)'],
    ['OG-Records', 'og-records', 'Records (OPA-Records)', 'OG'],
    ['OG-IT', 'og-it', 'Information Technology (OPA-IT)', 'OG'],
    ['OG-PESO', 'og-peso', 'Public Employment Services Office (OG-PESO)', 'OG'],
    ['OG-BAC', 'og-bac', 'Bids and Awards Committee (OG-BAC)', 'OG'],
    ['OG-CAO', 'og-cao', 'Community Affairs Office (OG-CAO)', 'OG'],
    ['OG-SDO', 'og-sdo', 'Sports Development Office (OG-SDO)', 'OG'],
    ['OG-BTS', 'og-bts', 'Benguet Technical School (OG-BTS)', 'OG'],
    ['OG-PDRRMO', 'og-pdrrmo', 'Provincial Disaster Risk Reduction Management Office (OG-PDRRMO)', 'OG'],
    ['OG-PTCAO', 'og-ptcao', 'Provincial Tourism and Cultural Affairs Office (OG-PTCAO)', 'OG'],
    ['OG-PWO', 'og-pwo', "Provincial Warden's Office (OG-PWO)", 'OG'],
    ['OG-Provincial Library', 'og-library', 'Provincial Library (OG-Library)', 'OG'],
    ['OVG', 'ovg', 'Office of the Vice-Governor (OVG)'],
    ['PAccO', 'pacco', 'Provincial Accounting Office (PAccO )'],
    ['PAgO', 'pago', 'Provincial Agriculture Office (PAgO)'],
    ['PAssO', 'passo', "Provincial Assessor's Office (PAssO)"],
    ['PBO', 'pbo', 'Provincial Budget Office (PBO)'],
    ['PEO', 'peo', 'Provincial Engineering Office (PEO)'],
    ['PENRO-LGU', 'penro-lgu', 'Provincial Environment and Natural Resources Office-LGU (PENRO-LGU)'],
    ['PGSO', 'pgso', 'Provincial General Services Office (PGSO)'],
    ['PHO', 'pho', 'Provincial Health Office (PHO)'],
    ['PHRMDO', 'phrmdo', 'Provincial Human Resource Management and Development Office (PHRMDO)'],
    ['PLO', 'plo', 'Provincial Legal Office (PLO)'],
    ['PPDO', 'ppdo', 'Provincial Plannning and Develoment Office (PPDO)'],
    ['PSWDO', 'pswdo', 'Provincial Social Welfare and Development Office (PSWDO)'],
    ['PTO', 'pto', "Provincial Treasurer's Office"],
    ['PVO', 'pvo', 'Provincial Veterinary Office'],
    ['OSSP', 'ossp', 'Office of the Sangguniang Panlalawigan (OSSP)'],
    ['ADH', 'adh', 'Atok District Hospital'],
    ['DMDH', 'dmdh', 'Dennis Molintas District Hospital'],
    ['IDH', 'idh', 'Itogon District Hospital'],
    ['KDH', 'kdh', 'Kapangan District Hospital'],
    ['NBDH', 'nbdh', 'Northern Benguet District Hospital'],
    ['BeGH', 'begh', 'Benguet General Hospital'],
];
