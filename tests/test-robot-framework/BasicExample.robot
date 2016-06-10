*** Settings ***
Library    Remote    http://${PHP_REMOTE_HOST}
Suite Teardown      Stop Remote Server

*** Variables ***
${PHP_REMOTE_HOST}    %{REMOTE_PHP_HOST}

*** Test Cases ***

Truth of life
    ${truthOfLife} =    Truth of Life
    Should Be Equal As Integers    42    ${truthOfLife }

Strings SHould Be Equal
    Strings Should Be Equal    Hello    Hello
