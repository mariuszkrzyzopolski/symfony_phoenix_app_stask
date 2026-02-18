Notatki w języku angielskim w ramach nawyku pisania dokumentacji w tym języku

My starting aproach is to prepare basic test cases for existing functionalities, fix any bugs found during this process, and then add new test cases for new functionalities. Base tool for checking completion rate will be coverage reports 

Issues and fixes:
- Temporary removed phoenix_live_dashboard dependency as it was crashing during phoenix app startup(Will try to fix it later)
- Added phoenix_live_view dependency to clear up warnings "application is not available"
- Corrected path to php test command in README.md
- Changed user removePhoto to delete photo with user, as ORM require photo to have user
- Test flooding logs with html structure during assert, resolved by checking only specific elements

TODO:
- Separate and move Likes to correct directories (Entity, Repository, Service)
- Replace RAW SQL queries with ORM
- Separate functions to services for better granularity. Priority is ProfileController
- phoenix tests return error (cannot invoke sandbox operation with pool DBConnection.ConnectionPool)
- Prepare login screen to symfony app. Similar form structure to import section

Done:
- Unit, integration and app tests cases for base application
- Coverage report generation with coverage driver added to docker image dev stage
- Task 2 functionality with importing photos from phoenix api and test coverage
- Basic complexity refactor for base components and tests


Additional tools used:
- Windsurf with SWE-1.5 as Code Agent Assistant (VS code fork)
- jan.ai (Agentic assistant for research)
