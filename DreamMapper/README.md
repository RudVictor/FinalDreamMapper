# DreamMapper — GTU BCA Final-Year Prototype

DreamMapper is a lightweight goal-mapping and execution dashboard built with PHP, MySQL, HTML5, CSS3 and Vanilla JavaScript. It is intentionally designed to be easy to install in XAMPP and easy to explain in a GTU viva.

## Main architecture

Browser → PHP pages/API → PDO → MySQL

The prototype's "Smart Dream Engine" is a local rule-based parser. It does not require an external AI API. This makes the demo reliable offline and gives you a clean viva explanation. You can later replace `process_dream.php` with an OpenAI/Gemini/local-model integration.

## Included features

- Dream prompt input
- Local smart decomposition into goals/milestones/backup route
- Cytoscape.js interactive roadmap
- Node status editing
- Auto-generated to-do list
- Task completion tracking
- XP/Dream Score
- Completion and risk calculations
- Success Probability Index
- Chart.js trend graph
- Notification panel
- Motivation quote system
- Dark/light mode
- Responsive layout
- PDO prepared statements
- MySQL foreign keys and indexes

## Project folder

Copy the entire `DreamMapper` folder to:

`C:\xampp\htdocs\dreammapper\`

Then visit:

`http://localhost/dreammapper/`

## Demo login

This classroom prototype automatically uses the demo user:

Email: demo@dreammapper.local
Password: password

The current `index.php` selects user ID 1 to keep the prototype simple. For a production project, add a proper login/register module and password verification.

## Viva explanation

### Smart processing
The user enters a natural-language dream. PHP detects common domain keywords and time expressions. It creates:
1. Main dream node
2. Definition milestone
3. Skill milestone
4. Portfolio milestone
5. Feedback milestone
6. Launch/execution milestone
7. Backup route

### Graph
Cytoscape.js receives nodes and parent-child edges from `api/get_dream.php`. Clicking a node opens a simple editor that calls `api/update_node.php`.

### Analytics
`analytics.php` calculates:
- Completion = completed nodes / total nodes × 100
- Risk Index = high-risk nodes / total nodes × 100
- Success Probability = bounded formula based on completion and risk
- Dream Score = completed nodes + node XP

### Database
The main entities are users, dreams, nodes, todos, motivation_media, user_analytics and notifications.

## Suggested future upgrades

- Real authentication
- CSRF protection
- Role-based administration
- Full node creation/deletion API
- Drag-and-drop graph positions
- Recurring notifications
- Real AI API
- YouTube Data API
- Device usage integration
- PDF progress reports
- Email/push notifications
