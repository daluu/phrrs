*** Settings ***
Library    Remote    http://${PHP_REMOTE_HOST}
Suite Teardown      Stop Remote Server

*** Variables ***
${PHP_REMOTE_HOST}    %{REMOTE_PHP_HOST}

*** Test Cases ***

List String
    ${listString} =    Create List    abc    def    ghi    jklmnopq
    ${fromKeyword} =    Remote.List String    ${listString}
    Should Be Equal    ${listString}    ${fromKeyword}

List Int
    ${int1} =    Convert to Integer    666
    ${int2} =    Convert to Integer    456
    ${int3} =    Convert to Integer    23
    ${listInt} =    Create List    ${int1}    ${int2}   ${int3}
    ${fromKeyword} =    Remote.List Int    ${listInt}
    Should Be Equal    ${listInt}    ${fromKeyword}
