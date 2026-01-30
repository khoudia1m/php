<?php

$file = 'taches.json';

function chargerTaches($filename) {
    if (!file_exists($filename)) {
        return [
            ['id' => 1, 'titre' => 'Tache 1 faire les exos', 'description' => 'faire les exos', 'statut' => 'En cours'],
            ['id' => 2, 'titre' => 'Faire les impressions', 'description' => 'Imprimer la liste des etudiants', 'statut' => 'Terminée']
        ];
    }
    $json_data = file_get_contents($filename);
    return json_decode($json_data, true) ?? [];
}

function sauvegarderTaches($filename, $data) {
    $json_data = json_encode(array_values($data), JSON_PRETTY_PRINT);
    file_put_contents($filename, $json_data);
}

$taches = chargerTaches($file);
$edit_task = null;

$self = $_SERVER['PHP_SELF'];

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'ajouter') {
        $new_id = time(); 
        $taches[] = [
            'id' => $new_id,
            'titre' => htmlspecialchars($_POST['titre']),
            'description' => htmlspecialchars($_POST['description']),
            'statut' => $_POST['statut']
        ];
    } 
    elseif ($_POST['action'] == 'enregistrer_modification') {
        foreach ($taches as &$task) {
            if ($task['id'] == $_POST['task_id']) {
                $task['titre'] = htmlspecialchars($_POST['titre']);
                $task['description'] = htmlspecialchars($_POST['description']);
                $task['statut'] = $_POST['statut'];
                break;
            }
        }
    }
    sauvegarderTaches($file, $taches);
    header("Location: " . $self);
    exit;
}

if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    $taches = array_filter($taches, function($task) use ($id_to_delete) {
        return $task['id'] != $id_to_delete;
    });
    sauvegarderTaches($file, $taches);
    header("Location: " . $self);
    exit;
}

if (isset($_GET['edit'])) {
    foreach ($taches as $task) {
        if ($task['id'] == $_GET['edit']) {
            $edit_task = $task;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tâches</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .form-container { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: white; max-width: 800px; margin: 20px auto; }
        .form-header { background-color: #0044cc; color: white; padding: 10px 15px; font-weight: 500; }
        .task-card { background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; height: 100%; }
        .badge-encours { background-color: #f1c40f; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; display: inline-block; }
        .badge-terminee { background-color: #1b5e20; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; display: inline-block; }
    </style>
</head>
<body class="p-6">

    <div class="max-w-6xl mx-auto">
        <h1 class="text-4xl font-light text-center mb-8">Gestion des Tâches</h1>

        <div class="form-container mb-12">
            <div class="form-header">
                <?php echo $edit_task ? 'Modifier la tâche' : 'Ajouter une tâche'; ?>
            </div>

            <form method="POST" action="<?php echo $self; ?>" class="p-6 space-y-4">
                <input type="hidden" name="action" value="<?php echo $edit_task ? 'enregistrer_modification' : 'ajouter'; ?>">
                <?php if ($edit_task): ?>
                    <input type="hidden" name="task_id" value="<?php echo $edit_task['id']; ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-gray-700 mb-1">Titre</label>
                    <input type="text" name="titre" required value="<?php echo $edit_task ? $edit_task['titre'] : ''; ?>" 
                           class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Description</label>
                    <textarea name="description" required 
                              class="w-full border border-gray-300 rounded p-2 h-24 focus:outline-none focus:ring-1 focus:ring-blue-500"><?php echo $edit_task ? $edit_task['description'] : ''; ?></textarea>
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Statut</label>
                    <select name="statut" class="w-full border border-gray-300 rounded p-2 bg-gray-50 appearance-none focus:outline-none">
                        <option value="En cours" <?php echo ($edit_task && $edit_task['statut'] == 'En cours') ? 'selected' : ''; ?>>En cours</option>
                        <option value="Terminée" <?php echo ($edit_task && $edit_task['statut'] == 'Terminée') ? 'selected' : ''; ?>>Terminée</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-[#004d26] text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition shadow-sm">
                        <?php echo $edit_task ? 'Enregistrer les modifications' : 'Ajouter la tâche'; ?>
                    </button>
                    <?php if ($edit_task): ?>
                        <a href="<?php echo $self; ?>" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition shadow-sm text-center">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div>
            <h2 class="text-3xl font-normal mb-6">Liste des tâches</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php if (empty($taches)): ?>
                    <p class="text-gray-500 italic">Aucune tâche pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($taches as $task): ?>
                        <div class="task-card">
                            <div>
                                <h3 class="text-xl font-medium mb-1"><?php echo $task['titre']; ?></h3>
                                <p class="text-gray-600 mb-4"><?php echo $task['description']; ?></p>
                                <div class="mb-4">
                                    <span class="<?php echo ($task['statut'] == 'Terminée') ? 'badge-terminee' : 'badge-encours'; ?>">
                                        <?php echo $task['statut']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="border-t pt-4 flex gap-2">
                                <a href="?edit=<?php echo $task['id']; ?>" class="bg-[#0022cc] text-white px-3 py-1 rounded text-sm text-center flex-1">Modifier</a>
                                <a href="?delete=<?php echo $task['id']; ?>" onclick="return confirm('Supprimer cette tâche ?')" 
                                   class="bg-[#cc2222] text-white px-3 py-1 rounded text-sm text-center flex-1">Supprimer</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
