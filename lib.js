const init = () => {
    document.querySelectorAll('.block_openai_questions-question .delete').forEach(button => {
        button.addEventListener('click', e => {
            e.target.closest('.block_openai_questions-question').remove()
        })
    })

    document.querySelector('#addToQBank').addEventListener('click', e => {
        const questions = buildQuestionObj()
        fetch('/blocks/openai_questions/api/question.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(questions)
        })
        .then(response => response.json())
        .then(data => {
            window.location.href = `/question/edit.php?courseid=${data.data.courseid}`
        })
    })
}

const buildQuestionObj = () => {
    let questions = {'questions': {}}
    document.querySelectorAll('.block_openai_questions-question').forEach(questionElem => {
        let answers = {}
        questionElem.querySelectorAll('input').forEach(answer => {
            answers[answer.dataset.qid] = answer.value.trim()
        })
        questions['questions'][questionElem.querySelector('textarea').textContent] = answers
    })
    questions['courseid'] = document.querySelector('#courseid').value;
    questions['qtype'] = document.querySelector('#qtype').value;

    return questions
}