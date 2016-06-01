*** Settings ***
Library    Remote    http://${PHP_REMOTE_HOST}
Suite Teardown      Stop Remote Server

*** Variables ***
${PHP_REMOTE_HOST}    %{REMOTE_PHP_HOST}

*** Test Cases ***

List String
    ${listString} =    Create List    abc    def    ghi    jklmnopq
    ${fromKeyword} =    Remote.List String    ${listString}
    Log    ${fromKeyword}
    Should Be Equal    ${listString}    ${fromKeyword}
