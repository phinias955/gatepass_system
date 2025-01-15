<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Goods Outward Gate Pass</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // JavaScript to dynamically add rows in the Goods Details table
    function addGoodsRow() {
      const tableBody = document.getElementById('goodsTableBody');
      const newRow = document.createElement('tr');
      newRow.innerHTML = `
        <td class="border border-gray-300 p-2 text-center"><input type="text" class="w-full border-none text-center" placeholder="S/N"></td>
        <td class="border border-gray-300 p-2"><input type="text" class="w-full border-none" placeholder="Item description"></td>
        <td class="border border-gray-300 p-2 text-center"><input type="text" class="w-full border-none text-center" placeholder="Qty"></td>
        <td class="border border-gray-300 p-2"><input type="text" class="w-full border-none" placeholder="Remarks"></td>
      `;
      tableBody.appendChild(newRow);
    }
  </script>
</head>
<body class="bg-gray-100 p-4">
  <div class="max-w-5xl mx-auto bg-white p-6 border border-gray-300 rounded-lg shadow-lg">
    <header class="text-center mb-6">
      <div class="flex justify-center items-center space-x-4">
        <img src="https://via.placeholder.com/80" alt="University Logo" class="w-20 h-20">
        <div>
          <h1 class="text-xl font-bold uppercase">Dar Es Salaam Tumaini University (DarTU)</h1>
          <h2 class="text-lg font-semibold mt-2 uppercase">Goods Outward Gate Pass</h2>
        </div>
      </div>
    </header>

    <!-- Applicant Details -->
    <section class="mb-6">
      <h3 class="font-semibold mb-2 uppercase">Applicant Details</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium">Name:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2" placeholder="Enter name">
        </div>
        <div>
          <label class="block text-sm font-medium">Telephone No:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2" placeholder="Enter phone number">
        </div>
        <div>
          <label class="block text-sm font-medium">Designation:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2" placeholder="Enter designation">
        </div>
        <div>
          <label class="block text-sm font-medium">Vehicle Registration No:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2" placeholder="Enter vehicle registration">
        </div>
        <div>
          <label class="block text-sm font-medium">Signature:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2" placeholder="Enter signature">
        </div>
        <div>
          <label class="block text-sm font-medium">Date:</label>
          <input type="date" class="w-full border border-gray-300 rounded p-2">
        </div>
      </div>
    </section>

    <!-- Goods Details -->
    <section class="mb-6">
      <h3 class="font-semibold mb-2 uppercase">Goods Details</h3>
      <table class="w-full border-collapse border border-gray-400 text-sm">
        <thead>
          <tr>
            <th class="border border-gray-300 p-2">S/N</th>
            <th class="border border-gray-300 p-2">Item Description</th>
            <th class="border border-gray-300 p-2">Qty</th>
            <th class="border border-gray-300 p-2">Remarks</th>
          </tr>
        </thead>
        <tbody id="goodsTableBody">
          <tr>
            <td class="border border-gray-300 p-2 text-center"><input type="text" class="w-full border-none text-center" placeholder="1"></td>
            <td class="border border-gray-300 p-2"><input type="text" class="w-full border-none" placeholder="Item description"></td>
            <td class="border border-gray-300 p-2 text-center"><input type="text" class="w-full border-none text-center" placeholder="Qty"></td>
            <td class="border border-gray-300 p-2"><input type="text" class="w-full border-none" placeholder="Remarks"></td>
          </tr>
        </tbody>
      </table>
      <button type="button" onclick="addGoodsRow()" class="mt-2 bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">Add Row</button>
    </section>

    <!-- Reason for Movement -->
    <section class="mb-6">
      <h3 class="font-semibold mb-2 uppercase">Reason for Movement</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
        <label class="flex items-center">
          <input type="radio" name="reason" class="mr-2"> Repair and Return
        </label>
        <label class="flex items-center">
          <input type="radio" name="reason" class="mr-2"> Disposal of Condemned Materials
        </label>
        <label class="flex items-center">
          <input type="radio" name="reason" class="mr-2"> Inter-Campus Transfer
        </label>
        <label class="flex items-center">
          <input type="radio" name="reason" class="mr-2"> Foreign Goods
        </label>
        <label class="flex items-center">
          <input type="radio" name="reason" class="mr-2"> Other
        </label>
      </div>
    </section>

    <!-- Department Details -->
    <section class="mb-6">
      <h3 class="font-semibold mb-2 uppercase">Department Details</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium">Name of Department:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Name and Signature of HoD:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Name of HoD:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2">
        </div>
      </div>
    </section>

    <!-- Estate Office -->
    <section class="mb-6">
      <h3 class="font-semibold mb-2 uppercase">Estate Office</h3>
      <div class="mb-2">
        <p class="text-sm">Permission has been: <span class="font-semibold">Granted / Not Granted</span></p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium">Security Officer/Estate Manager:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Signature:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2">
        </div>
      </div>
    </section>

    <!-- For Main Gate Inspection -->
    <section class="mb-6">
      <h3 class="font-semibold mb-2 uppercase">For Main Gate Inspection Only</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium">Goods Inspected By:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Time:</label>
          <input type="time" class="w-full border border-gray-300 rounded p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Remarks:</label>
          <input type="text" class="w-full border border-gray-300 rounded p-2">
        </div>
        <div>
          <label class="block text-sm font-medium">Date:</label>
          <input type="date" class="w-full border border-gray-300 rounded p-2">
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium">Signature:</label>
        <input type="text" class="w-full border border-gray-300 rounded p-2">
      </div>
    </section>

    <!-- Submit and Reset Buttons -->
    <div class="text-center">
      <button class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">Submit</button>
      <button type="reset" class="bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600 ml-4">Reset</button>
    </div>
  </div>
</body>
</html>
