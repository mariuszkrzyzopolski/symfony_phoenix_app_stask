Notatki w języku angielskim w ramach nawyku pisania dokumentacji w tym języku

My starting aproach is to prepare basic test cases for existing functionalities, fix any bugs found during this process, and then add new test cases for new functionalities. Base tool for checking completion rate will be coverage reports 

Issues and fixes:
- Temporary removed phoenix_live_dashboard dependency as it was crashing during phoenix app startup(Will try to fix it later)
- Added phoenix_live_view dependency to clear up warnings "application is not available"
- Corrected path to php test command in README.md
- Changed user removePhoto to delete photo with user, as ORM require photo to have user

Not yet solved:
- phoenix tests return error (cannot invoke sandbox operation with pool DBConnection.ConnectionPool)

TODO:
- Prepare login screen and import screen to symfony app(should be similar to each other so it will be easier to implement)
- Simplify complex methods such as like, removePhoto (look coverage report for details)
- Separate and move Likes to correct directories (Entity, Repository, Service)
- Replace RAW SQL queries with ORM

Done:
- Unit, integration and app tests cases for base application
- Coverage report generation with coverage driver added to docker image dev stage

Additional tools used:
- Windsurf (VS code fork)
- jan.ai (Agentic assistant for research)
