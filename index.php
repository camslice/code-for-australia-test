<link href="https://unpkg.com/basscss@8.0.2/css/basscss.min.css" rel="stylesheet">

<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$DB = @new mysqli('localhost', 'root', 'root', 'code-for-australia');

if ($DB->connect_error) {
    echo "Error: " . $DB->connect_error;
    exit();
}

$validationError = false;

$sanitise = function ($data, $length) use ($DB) {
  return $output = mysqli_real_escape_string($DB, substr($data, 0, $length));
};

if (isset($_POST['submit'])) {
    echo '<div class="center p2">
            <div class="max-width-3 mx-auto left-align">';
    $required = '';
    if (isset($_POST['namefirst']) && !empty($_POST['namefirst'])) {
        $namefirst = $sanitise($_POST['namefirst'], 32);
    } else {
        echo '<strong>Given name(s)</strong> field is required<br/>';
        $validationError = true;
    }
    
    if (isset($_POST['namelast']) && !empty($_POST['namelast'])) {
        $namelast = $sanitise($_POST['namelast'], 32);
    } else {
        echo '<strong>Surname</strong> field is required<br/>';
        $validationError = true;
    }
    
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $email = $sanitise($_POST['email'], 64);
    } else {
        echo '<strong>Email</strong> field is required<br/>';
        $validationError = true;
    }

    if (isset($_POST['age']) && !empty($_POST['age'])) {
        $age = (int) $sanitise($_POST['age'], 3);
    } else {
        echo '<strong>Age</strong> field is required<br/>';
        $validationError = true;
    }
    
    if (isset($_POST['fellowship']) && !empty($_POST['fellowship'])) {
        $fellowship = $sanitise($_POST['fellowship'], 16);
    } else {
        echo '<strong>Fellowship</strong> field is required<br/>';
        $validationError = true;
    }
    
    if (isset($_POST['about']) && !empty($_POST['about'])) {
        $about = $sanitise($_POST['about'], 2048);
    } else {
        echo '<strong>About</strong> field is required<br/>';
        $validationError = true;
    }
    
    if ($validationError) {
        echo "<br/>Form submission was invalid, please correct the errors above.";
//        header('Location: index.php');
//        exit();
    } else {
        $timestamp = $sanitise(date("l, F n, Y g:i A", time()), 64);
        $id = $sanitise(hash('sha256', $timestamp), 24);
        if ($fellowship === 'uncategorised') {
            $insert = $DB->query("INSERT INTO fellows (id, isActive, age, namefirst, namelast, email, about, registered)
                                  VALUES ('$id', 1, $age, '$namefirst', '$namelast', '$email', '$about', '$timestamp')");
        } else {
            $insert = $DB->query("INSERT INTO fellows (id, isActive, age, fellowship, namefirst, namelast, email, about, registered)
                                  VALUES ('$id', 1, $age, '$fellowship', '$namefirst', '$namelast', '$email', '$about', '$timestamp')");
        }
        echo "New fellow <strong>" . $namefirst . $namelast . "</strong> successfully added to register under the <strong>" . $fellowship . "</strong> fellowship category";
    }
    
    echo '</div></div><hr/>';
}

$result = $DB->query("SELECT namefirst, namelast, age, fellowship
                      FROM fellows
                      GROUP BY fellowship, age, namefirst, namelast");

$fellowsGrouped = [];

foreach ($result as $row) {
    $fellowship = $row['fellowship'];
    if ($fellowship === null) {
        $fellowship = "Uncategorised";
    }
    if (!isset($fellowsGrouped[$fellowship])) {
        $fellowsGrouped[$fellowship] = [];
    }
    $details         = [];
    $details['name'] = $row['namefirst'] . " " . $row['namelast'];
    $details['age']  = $row['age'];
    array_push($fellowsGrouped[$fellowship], $details);
}

$youngestAge = 200;
$oldestAge   = 0;

?>

<div class="center p2">
  <div class="max-width-3 mx-auto left-align">
    <h1>Fellowship Register</h1>
    <hr/>
      <?php
      
      foreach ($fellowsGrouped as $fellowship => $fellows) {
          $average = array_sum(array_column($fellows, 'age')) / count($fellows);
          echo '<div class="flex flex-wrap">
            <p class="col-6">Fellowship category name: <strong>' . $fellowship . '</strong></p>
            <p class="col-6 right-align">Average fellow age for this category: <strong>' . $average . '</strong></p>
          </div>';
          echo '<p class="mb0">Fellows:</p>
          <div class="flex flex-wrap mb2 pb2 mxn2">';
          foreach ($fellows as $fellow) {
              echo '<div class="col-4 p2 border-box">
                <div class="p2 border">
                  <p>Name: ' . $fellow['name'] . '<br/>
                     Age: ' . $fellow['age'] . '</p>';
              
              if ($fellow['age'] < $youngestAge) {
                  $youngestAge                  = $fellow['age'];
                  $youngestFellow               = $fellow;
                  $youngestFellow['fellowship'] = $fellowship;
              }
              if ($fellow['age'] > $oldestAge) {
                  $oldestAge                  = $fellow['age'];
                  $oldestFellow               = $fellow;
                  $oldestFellow['fellowship'] = $fellowship;
              }
              echo "</div></div>";
          }
          echo "</div><hr/>";
      }
      
      echo '
</div></div>
<hr/>
<div class="center p2">
    <div class="max-width-3 mx-auto left-align">
      <h2>Youngest and Oldest</h2>
      <div class="flex flex-wrap mxn2">
        <div class="col-6 px2 border-box">
          <h4>Youngest fellow:</h4>
          <div class="p2 border">
            <p>Name: ' . $youngestFellow['name'] . '<br/>
               Age: ' . $youngestFellow['age'] . '<br/><br/>
               Fellowship category: ' . $youngestFellow['fellowship'] . '</p>
          </div>
        </div>
        <div class="col-6 px2 border-box">
          <h4>Oldest fellow:</h4>
          <div class="p2 border">
            <p>Name: ' . $oldestFellow['name'] . '<br/>
               Age: ' . $oldestFellow['age'] . '<br/><br/>
               Fellowship category: ' . $oldestFellow['fellowship'] . '</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <hr class="mt4"/>
  <div class="center p2">
    <div class="max-width-3 mx-auto left-align">
      <form method="post" action="">
        <h2 class="mb0">Add a fellow</h2>
        <h5 class="mt1">(all fields required)</h5>
        <div class="flex flex-wrap mxn2 left-align">
          <div class="col-6 flex flex-column p2 border-box">
            <input type="text" name="namefirst" placeholder="Given name(s)" class="mb2 p1"/>
            <input type="text" name="namelast"  placeholder="Surname name" class="mb2 p1"/>
            <input type="email" name="email" placeholder="Email" class="mb2 p1"/>
            <input type="number" name="age" min="13" max="150" placeholder="Age" class="mb2 p1"/>
            <select name="fellowship">
              <option value="BLA">BLA</option>
              <option value="CityZap">CityZap</option>
              <option value="Relwp">Relwp</option>
              <option value="VicWays">VicWays</option>
              <option value="ZESE">ZESE</option>
              <option value="uncategorised">Uncategorised</option>
            </select>
          </div>
          <div class="col-6 p2 border-box">
            <label>About:</label>
            <textarea rows="16" maxlength="2048" class="col-12" name="about"></textarea>
          </div>
        </div>
        <div class="center mb3">
          <button name="submit" class="col-6 p2 mx-auto">Add fellow</button>
        </div>
      </form>
    </div>
  </div>';
  
$result->close();
$DB->close();

?>
