package main

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"regexp"
	"slices"
	"strings"
	"syscall"
	"text/template"
	"time"
)

type Form struct {
	FIO              string
	Phone            string
	Email            string
	Birthday         string
	Gender           string
	Languages        []string
	Biography        string
	ContractAccepted bool
}

type PageData struct {
	Form  Form
	Error error
	Sent  bool
}

var db *sql.DB
var templates *template.Template
var l *log.Logger

func main() {
	l = log.New(os.Stdout, "form ", log.LstdFlags)
	db, err := sql.Open("mysql", "u68797:6204726@/u68797")
	if err != nil {
		l.Fatalln("can't connect do db")
	}
	defer db.Close()

	templates = template.Must(template.ParseGlob("templates/*.html"))

	sm := http.NewServeMux()
	sm.HandleFunc("/index.html", showForm)
	sm.HandleFunc("/submit", handleSubmit)

	s := http.Server{
		Addr:     "0.0.0.0:8000",
		Handler:  sm,
		ErrorLog: l,
	}

	go func() {
		l.Println("starting server")

		err := s.ListenAndServe()

		if err != nil {
			l.Fatalln("error to start server")
		}
	}()

	sig := make(chan os.Signal, 1)
	signal.Notify(sig, os.Interrupt, syscall.SIGTERM)
	<-sig

	ctx, _ := context.WithTimeout(context.Background(), 10*time.Second)
	s.Shutdown(ctx)
}

func showForm(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, "method is not supported", http.StatusMethodNotAllowed)
		return
	}

	data := PageData{
		Form: Form{},
	}

	err := templates.ExecuteTemplate(w, "index.html", data)
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
	}
}

func handleSubmit(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "method is not supported", http.StatusMethodNotAllowed)
		return
	}

	err := r.ParseForm()
	if err != nil {
		http.Error(w, "error to parse form", http.StatusBadRequest)
		return
	}

	form := Form{
		FIO:              r.FormValue("fio"),
		Phone:            r.FormValue("phone"),
		Email:            r.FormValue("email"),
		Birthday:         r.FormValue("birthday"),
		Gender:           r.FormValue("gender"),
		Languages:        r.Form["languages"],
		Biography:        r.FormValue("biography"),
		ContractAccepted: r.FormValue("contract_accepted") == "on",
	}

	validationError := form.Validate()

	data := PageData{
		Form:  form,
		Error: validationError,
		Sent:  errors.Is(validationError, nil),
	}
	templates.ExecuteTemplate(w, "index.html", data)

	err = form.insertToDB()
	if err != nil {
		l.Println(err)
	}
}

func (f *Form) Validate() error {
	if !f.ContractAccepted {
		return fmt.Errorf("contract is not accepted")
	}

	if f.FIO == "" {
		return fmt.Errorf("fio is required")
	}

	if !regexp.MustCompile(`^[A-Za-zА-Яа-я\s]{5,150}$`).MatchString(f.FIO) {
		return fmt.Errorf("uncorrect fio format")
	}

	if f.Phone == "" {
		return fmt.Errorf("phone is required")
	}

	if !regexp.MustCompile(`^\+?[0-9\s-]{10,20}$`).MatchString(f.Phone) {
		return fmt.Errorf("uncorrect phone format")
	}

	if f.Email == "" {
		return fmt.Errorf("email is required")
	}

	if !regexp.MustCompile(`^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$`).MatchString(f.Email) {
		return fmt.Errorf("uncorrect email format")
	}

	if f.Birthday == "" {
		return fmt.Errorf("birthday is required")
	}

	if len(f.Languages) == 0 {
		return fmt.Errorf("must select at least one language")
	}

	if f.Gender == "" {
		return fmt.Errorf("gender is required")
	}

	if f.Biography == "" {
		return fmt.Errorf("biography is required")
	}

	validLanguages := []string{
		"Pascal", "C", "C++", "JavaScript", "PHP", "Python", "Java", "Haskel", "Clojure", "Prolog", "Scala", "Go",
	}

	for _, lang := range f.Languages {
		if !slices.Contains(validLanguages, lang) {
			return fmt.Errorf("unvalid programming language")
		}
	}

	return nil
}

func (f *Form) insertToDB() error {
	tx, err := db.Begin()
	if err != nil {
		return err
	}

	ps, err := tx.Prepare(
		`INSERT INTO users (fio, phone, email, birth_date, gender, biography) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
		`)

	if err != nil {
		tx.Rollback()
		return err
	}
	defer ps.Close()

	bd, _ := time.Parse("2006-01-02", f.Birthday)
	result, err := ps.Exec(
		f.FIO,
		f.Phone,
		f.Email,
		bd.Format("2006-01-02"),
		f.Gender,
		f.Biography,
	)
	if err != nil {
		tx.Rollback()
		return err
	}

	userID, err := result.LastInsertId()
	if err != nil {
		tx.Rollback()
		return err
	}

	var placeholders []string
	var values []any
	for _, lang := range f.Languages {
		placeholders = append(placeholders, "(?, (SELECT id FROM programming_languages WHERE name = ?))")
		values = append(values, userID, lang)
	}

	query := "INSERT INTO user_programming_languages (user_id, language_id) VALUES " +
		strings.Join(placeholders, ",")

	_, err = tx.Exec(query, values...)
	if err != nil {
		tx.Rollback()
		return err
	}

	if err := tx.Commit(); err != nil {
		return err
	}

	return nil
}
