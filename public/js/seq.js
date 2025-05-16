    
    function getCheckboxValue() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        let total = 0;
        let isChecked = false;  

        checkboxes.forEach((checkbox, index) => {
            if (checkbox.checked) {
                isChecked = true;  
           
                switch (index) {
                    case 0: total += 16; break; // 第1個
                    case 1: total += 8; break;  // 第2個
                    case 2: total += 4; break;  // 第3個
                    case 3: total += 2; break;  // 第4個
                    case 4: total += 1; break;  // 第5個
                }
            }
        });


        return total;
    }