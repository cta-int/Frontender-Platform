const chalk = require("chalk");

(async () => {
    console.log(chalk.blue("Welcome to the"));
    console.log(
        chalk.green(" ______               _                 _           ")
    );
    console.log(
        chalk.green("|  ____|             | |               | |          ")
    );
    console.log(
        chalk.green("| |__ _ __ ___  _ __ | |_ ___ _ __   __| | ___ _ __ ")
    );
    console.log(
        chalk.green("|  __| '__/ _ \\| '_ \\| __/ _ \\ '_ \\ / _` |/ _ \\ '__|")
    );
    console.log(
        chalk.green("| |  | | | (_) | | | | ||  __/ | | | (_| |  __/ |   ")
    );
    console.log(
        chalk.green("|_|  |_|  \\___/|_| |_|\\__\\___|_| |_|\\__,_|\\___|_|   ")
    );
    console.log(
        chalk.blue("                                        wizard 0.0.1")
    );
    console.log();

    let inquirer = require("inquirer"),
        answers = await inquirer.prompt([
            {
                name: "action",
                message: "What would you like to do?",
                type: "list",
                choices: [
                    {
                        name: "Edit (or add) MongoDB credentials",
                        value: "check_mongo"
                    },
                    {
                        name: "Upgrade to version 1.0.0",
                        value: "upgrade_100"
                    }
                ]
            }
        ]);

    console.log(answers);
})();
