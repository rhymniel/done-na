function showLoginCredentials() {
    let studentID = sessionStorage.getItem("studentID");
    let lastName = sessionStorage.getItem("lastName");
    
    if (studentID && lastName) {
        let password = lastName.toLowerCase() + '8080';
        alert(`Enrollment Successful! \n\nUsername: ${studentID} \nPassword: ${password}`);
        
        // Clear the session storage
        sessionStorage.removeItem("studentID");
        sessionStorage.removeItem("lastName");
    }
}