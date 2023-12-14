const init = (Y, courseid) => {
    document.querySelectorAll('.block_openai_questions-question .block_openai_questions-delete').forEach(button => {
        button.addEventListener('click', e => {
            removeQuestion(e.target.closest('.block_openai_questions-question'))
        })
    })

    document.querySelector('#addToQBank').addEventListener('click', async e => {
        e.target.style.opacity = '0.5'
        e.target.value = "Please wait..."
        e.target.style.pointerEvents = 'none'
        
        const questions = buildQuestionObj()
        await fetch('/blocks/openai_questions/api/question.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(questions)
        })

        window.location.href = `/question/edit.php?courseid=${courseid}`
    })

    document.querySelectorAll('.block_openai_questions-markCorrectButton').forEach(button => {
        button.addEventListener('click', (e) => {
            // If the AI didn't mark a correct answer for this question, this will fail
            // let's just catch it and ignore that
            try {
                e.target.closest('.block_openai_questions-text-container').querySelector('.block_openai_questions-correct').classList.remove('block_openai_questions-correct')
            } catch (e) {}
            e.target.parentElement.querySelector('input').classList.add('block_openai_questions-correct')
        })
    })
}

const buildQuestionObj = () => {
    let questions = {'questions': {}}
    document.querySelectorAll('.block_openai_questions-question').forEach(questionElem => {
        let answers = {}
        let correct = 'A';
        questionElem.querySelectorAll('input').forEach(answer => {
            if (answer.classList.contains('block_openai_questions-correct')) {
                correct = answer.dataset.qid
            }
            answers[answer.dataset.qid] = answer.value.trim()
        })
        questions['questions'][questionElem.querySelector('textarea').value] = {}
        questions['questions'][questionElem.querySelector('textarea').value]['answers'] = answers
        questions['questions'][questionElem.querySelector('textarea').value]['correct'] = correct
    })
    questions['courseid'] = document.querySelector('#courseid').value;
    questions['qtype'] = document.querySelector('#qtype').value;

    return questions
}

const removeQuestion = (elem) => {
    elem.style.opacity = '0'
    elem.style.maxHeight = elem.clientHeight + 'px'
    window.setTimeout(() => {

        elem.style.maxHeight = '0px'
        elem.style.padding = '0'
        elem.style.marginTop = '-1rem'

        window.setTimeout(() => {
            elem.remove()
        }, 400)

    }, 150)
}