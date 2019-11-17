# What
Dead Simple script to get word count from slack channels history

# Usage

Set environment variable with your slack api token

``export SLACK_API_TOKEN='YourToken'``


 Now, just run it
 
 ```php application.php CHANNELID1,CHANNELID2 composer,docker,container,pipeline```
 
 Output is similar to 
 
 ```
 +-----------+----------+-------+
 | Word      | Month    | Count |
 +-----------+----------+-------+
 | composer  | Nov 2019 | 124   |
 | docker    | Nov 2019 | 97    |
 | container | Nov 2019 | 51    |
 | pipeline  | Nov 2019 | 15    |
 | composer  | Oct 2019 | 454   |
 | docker    | Oct 2019 | 241   |
 | container | Oct 2019 | 108   |
 | pipeline  | Oct 2019 | 82    |
 | composer  | Sep 2019 | 104   |
 | docker    | Sep 2019 | 75    |
 | container | Sep 2019 | 25    |
 | pipeline  | Sep 2019 | 59    |
 | composer  | Aug 2019 | 109   |
 | docker    | Aug 2019 | 63    |
 | container | Aug 2019 | 47    |
 | pipeline  | Aug 2019 | 24    |
 +-----------+----------+-------+
 ```
 
 also it caches some data in /tmp/ 