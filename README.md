Installation du projet:

    cloner le projet avec -> git clone https://github.com/NicolasDufresne/devoir_florian

    dans le .env, changer les informations de connexion à la bdd

    créer la base données et appliquer les migrations avec -> php bin/console doctrine:database:create puis -> php bin/console make:migration et enfin -> php bin/console doctrine:migrations:migrate

    lancer le serveur avec -> symfony server:start

    votre serveur est lancé, rdv sur l'url / pour arriver sur la page d'accueil

    pour peupler la bdd, vous pouvez utiliser la route /medias/{token} avec la méthode POST (vous devez fournir dans l'url un token de sécurité, récupérable grâce à la route /token)

