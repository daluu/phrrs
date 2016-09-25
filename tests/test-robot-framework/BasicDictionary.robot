*** Settings ***
Library    Remote    http://${PHP_REMOTE_HOST}
Suite Teardown      Stop Remote Server

*** Variables ***
${PHP_REMOTE_HOST}    %{REMOTE_PHP_HOST}

*** Test Cases ***

Dictionary String
    ${dictionary} =    Create Dictionary    yop=abc    yup=def    yep=ghi    boooooom=jklmnopq
    ${fromKeyword} =    Remote.Dictionary String    ${dictionary}
    Should Be Equal    ${dictionary}    ${fromKeyword}

Dictionary Int
    ${int1} =    Convert to Integer    666
    ${int2} =    Convert to Integer    456
    ${int3} =    Convert to Integer    23
    ${dictionary} =    Create Dictionary    yeah=${int1}    notmyfault=${int2}   YoUrEtHeOnE=${int3}
    ${fromKeyword} =    Remote.Dictionary Int    ${dictionary}
    Should Be Equal    ${dictionary}    ${fromKeyword}
