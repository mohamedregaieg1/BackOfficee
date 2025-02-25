<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Congé</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: navy; text-align: center; }
        .logo { width: 150px; display: block; margin: auto; }
        .status { width: 100px; display: block; margin: 20px auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        .approved { color: green; }
        .rejected { color: red; }
    </style>
</head>
<body>
    <!-- Affichage du logo de l'entreprise -->
    <img src="data:image/png;base64,<?php echo e($companyLogoBase64); ?>" class="logo">

    <h1>Demande de Congé</h1>

    <!-- Affichage du statut avec l'image appropriée -->
    <img src="data:image/png;base64,<?php echo e($statusImageBase64); ?>" class="status">

    <table>
        <tr>
            <th>Employé</th>
            <td><?php echo e(Auth::user()->first_name); ?> <?php echo e(Auth::user()->last_name); ?></td>
        </tr>
        <tr>
            <th>Début</th>
            <td><?php echo e($leave->start_date); ?></td>
        </tr>
        <tr>
            <th>Fin</th>
            <td><?php echo e($leave->end_date); ?></td>
        </tr>
        <tr>
            <th>Raison</th>
            <td><?php echo e($leave->reason); ?></td>
        </tr>
        <tr>
            <th>Statut</th>
            <td class="<?php echo e($leave->status == 'approved' ? 'approved' : 'rejected'); ?>">
                <?php echo e(ucfirst($leave->status)); ?>

            </td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH C:\Users\MSI\OneDrive\Documents\GitHub\BackOfficee\resources\views/pdf/leave.blade.php ENDPATH**/ ?>